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
			'supervision' => array (
				'description' => false,
				'url' => '',
				'tab' => 'Sign up to a supervision',
				'icon' => 'pencil',
			),
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
			
			# Redirect the user
			$redirectTo = $this->baseUrl . '/' . $supervisionId . '/';
			$html .= application::sendHeader (302, $redirectTo, true);
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Supervision page
	public function supervision ($id)
	{
		# Start the HTML
		$html = '';
		
		# Ensure the ID is present
		if (!$id) {
			$this->page404 ();
			return;
		}
		
		# Obtain this supervision or end
		if (!$supervision = $this->getSupervision ($id)) {
			$this->page404 ();
			return;
		}
		
		# Add title
		$html .= "\n<h2>Sign up to a supervision</h2>";
		
		# Extract the timeslots
		$timeslots = $supervision['timeslots'];
		unset ($supervision['timeslots']);
		
		# Get the person name
		$userLookupData = camUniData::getLookupData ($supervision['username']);
		
		# Create the supervision page
		$headings = $this->databaseConnection->getHeadings ($this->settings['database'], $this->settings['table']);
		
		$html .= "\n<h3>" . htmlspecialchars ($supervision['title']) . '</h3>';
		$html .= "\n<p>With: <strong>" . htmlspecialchars ($userLookupData['name']) . '</strong></p>';
		$html .= "\n<br />";
		$html .= "\n<h4>Description:</h4>";
		$html .= "\n<div class=\"graybox\">";
		$html .= "\n" . $supervision['descriptionHtml'];
		$html .= "\n</div>";
		if ($supervision['readingListHtml']) {
			$html .= "\n<h4>Reading list:</h4>";
			$html .= "\n<div class=\"graybox\">";
			$html .= "\n" . $supervision['readingListHtml'];
			$html .= "\n</div>";
		}
		$html .= "\n</div>";
		$html .= "\n<br />";
		$html .= "\n<h4>Time slots:</h4>";
		
		# Determine the posted slot
		if (isSet ($_POST['timeslot']) && is_array ($_POST['timeslot']) && count ($_POST['timeslot']) == 1) {
			$submittedId = key ($_POST['timeslot']);
			if (in_array ($submittedId, $timeslots)) {
				echo $submittedId;
			}
		}
		
		# Arrange timeslots by date
		$timeslotsByDate = array ();
		foreach ($timeslots as $id => $startTime) {
			$dateFormatted = date ('jS F Y', strtotime ($startTime));
			$timeFormatted = date ('H:i', strtotime ($startTime)) . ' - ' . date ('H:i', strtotime ($startTime) + ($supervision['length'] * 60));
			$timeslotsByDate[$dateFormatted][$id] = $timeFormatted;
		}
		
		# Create the timeslot buttons
		$html .= "\n<form name=\"timeslot\" action=\"\" method=\"post\">";
		foreach ($timeslotsByDate as $dateFormatted => $timeslotsForDate) {
			$html .= "\n<h5>{$dateFormatted}</h5>";
			foreach ($timeslotsForDate as $id => $timeFormatted) {
				$indexValue = $timeslots[$id];
				$html .= "\n<input type=\"submit\" name=\"timeslot[{$indexValue}]\" value=\"{$timeFormatted}\" />";		// See multiple button solution using [] at: http://stackoverflow.com/a/34915274/180733
			}
		}
		$html .= "\n</form>";
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to get data for a single supervision
	private function getSupervision ($id)
	{
		# Obtain the supervision data or end
		if (!$supervision = $this->databaseConnection->selectOne ($this->settings['database'], $this->settings['table'], array ('id' => $id))) {
			return false;
		}
		
		# Add the timeslot data or end
		if (!$supervision['timeslots'] = $this->databaseConnection->selectPairs ($this->settings['database'], 'timeslots', array ('supervisionId' => $id), array ('id', 'startTime'), true, $orderBy = 'startTime')) {
			return false;
		}
		
		# Return the collection
		return $supervision;
	}
}

?>