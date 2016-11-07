<?php

# Class to create a simple supervision signup system


require_once ('frontControllerApplication.php');
class supervisionSignup extends frontControllerApplication
{
	# Function to assign defaults additional to the general application defaults
	public function defaults ()
	{
		# Specify available arguments as defaults or as NULL (to represent a required argument)
		$defaults = array (
			'applicationName' => 'Supervision signup',
			'div' => strtolower (__CLASS__),
			'database' => 'supervisions',
			'table' => 'supervisions',
			'useCamUniLookup' => true,
			'emailDomain' => 'cam.ac.uk',
			'administrators' => true,
			'userIsStaffCallback' => 'userIsStaffCallback',		// Callback function
			'coursesCallback' => 'coursesCallback',				// Callback function
			'authentication' => true,
			'databaseStrictWhere' => true,
		);
		
		# Return the defaults
		return $defaults;
	}
	
	
	# Function assign additional actions
	public function actions ()
	{
		# Specify additional actions
		$actions = array (
			'add' => array (
				'description' => 'Create a new supervision',
				'url' => 'add/',
				'tab' => 'Create a new supervision',
				'icon' => 'add',
				'enableIf' => $this->userIsStaff,
			),
		);
		
		# Return the actions
		return $actions;
	}
	
	
	# Database structure definition
	public function databaseStructure ()
	{
		return "
			CREATE TABLE IF NOT EXISTS `administrators` (
			  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Username' PRIMARY KEY,
			  `active` enum('','Yes','No') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Yes' COMMENT 'Currently active?',
			  `privilege` enum('Administrator','Restricted administrator') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Administrator' COMMENT 'Administrator level'
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='System administrators';
		";
	}
	
	
	# Additional initialisation, pre-actions
	public function mainPreActions ()
	{
		# Determine if the user is staff
		$userIsStaffCallbackFunction = $this->settings['userIsStaffCallback'];
		$this->userIsStaff = ($this->user ? $userIsStaffCallbackFunction ($this->user) : false);
		
	}
	
	
	# Welcome screen
	public function home ()
	{
		# Start the HTML
		$html = '';
		
		# Give link for staff
		if ($this->userIsStaff) {
			$html .= "\n<h2>Create supervision signup sheet</h2>";
			$html .= "\n<p>As a member of staff, you can <a href=\"{$this->baseUrl}/add/\" class=\"actions\"><img src=\"/images/icons/add.png\" alt=\"Add\" border=\"0\" /> create a supervision signup sheet</a>.</p>";
		}
		
		# Return the HTML
		echo $html;
	}
	
	
	# Function to create a new supervision
	public function add ()
	{
		# Start the HTML
		$html = '';
		
		# Show the form
		$html .= $this->supervisionForm ();
		
		# Return the HTML
		echo $html;
	}
	
	
	# Supervision editing form
	private function supervisionForm ()
	{
		# Start the HTML
		$html = '';
		
		# Get the courses from the callback
		$coursesCallback = $this->settings['coursesCallback'];
		$courses = $coursesCallback ();
		
		# Databind a form
		$form = new form (array (
			'displayRestrictions' => false,
			'formCompleteText' => false,
			'databaseConnection' => $this->databaseConnection,
			'richtextEditorToolbarSet' => 'Basic',
			'richtextWidth' => 650,
			'richtextHeight' => 250,
			'cols' => 80,
			'autofocus' => true,
			'unsavedDataProtection' => true,
		));
		$form->dataBinding (array (
			'database' => $this->settings['database'],
			'table' => $this->settings['table'],
			'intelligence' => true,
			'exclude' => array ('username', 'courseName'),		// Fixed data fields, handled below
			'attributes' => array (
				'courseId' => array ('heading' => array (3 => 'Supervision details'), 'type' => 'select', 'values' => $courses, ),
				'studentsPerTimeslot' => array ('heading' => array (3 => 'Dates, times and locations'), 'type' => 'number', ),	#!# Shouldn't need to specify type - is an ultimateForm bug
			),
		));
		$form->input (array (
			'name' => 'timeslots',
			'title' => 'Timeslots (start time of each available supervision)',
			'expandable' => "\n",
			'required' => true,
		));
		if ($result = $form->process ($html)) {
			
			# Add in fixed data
			$result['username'] = $this->user;
			$result['updatedAt'] = 'NOW()';
			
			# Add in the course name so that this not dependent on a live feed which may change from year to year
			$coursesFlattened = application::flattenMultidimensionalArray ($courses);
			$result['courseName'] = $coursesFlattened[$result['courseId']];
			
			# Extract the timeslots for entering in the separate timeslot table
			$timeslots = explode ("\n", $result['timeslots']);
			unset ($result['timeslots']);
			
			# Insert the new supervision into the database
			if (!$this->databaseConnection->insert ($this->settings['database'], $this->settings['table'], $result)) {
				$this->throwError ('There was a problem creating the new supervision signup sheet.', false, application::dumpData ($this->databaseConnection->error (), false, true));
				echo $html;
				return false;
			}
			
			# Get the supervision ID just inserted
			$supervisionId = $this->databaseConnection->getLatestId ();
			
			# Unique the list of timeslots
			$timeslots = array_unique ($timeslots);
			
			# Add each timeslot
			$timeslotInserts = array ();
			foreach ($timeslots as $index => $timeslot) {
				$timeslotInserts[] = array (
					'supervisionId' => $supervisionId,
					'startTime' => $timeslot,
				);
			}
			
			# Insert the new timeslots
			if (!$this->databaseConnection->insertMany ($this->settings['database'], 'timeslots', $timeslotInserts)) {
				$html .= '<p class="warning">There was a problem creating the new supervision signup sheet.</p>';
				return false;
			}
		}
		
		# Return the HTML
		return $html;
	}
	
}

?>