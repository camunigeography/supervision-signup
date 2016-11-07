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
			'userYeargroupCallback' => 'userYeargroupCallback',	// Callback function
			'authentication' => true,
			'databaseStrictWhere' => true,
			'lengths' => array (30 => '30 minutes', 45 => '45 minutes', 60 => '1 hour', 90 => 'Hour and a half', 120 => 'Two hours', ),
			'lengthDefault' => 60,
			'yearGroups' => array ('Part IA', 'Part IB', 'Part II'),
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
			'courses' => array (
				'description' => 'Courses',
				'url' => 'courses/',
				'tab' => 'Courses',
				'icon' => 'page_white_stack',
				'administrator' => true,
			),
		);
		
		# Return the actions
		return $actions;
	}
	
	
	# Database structure definition
	public function databaseStructure ()
	{
		return "
			
			-- Administrators
			CREATE TABLE IF NOT EXISTS `administrators` (
			  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Username' PRIMARY KEY,
			  `active` enum('','Yes','No') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Yes' COMMENT 'Currently active?',
			  `privilege` enum('Administrator','Restricted administrator') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Administrator' COMMENT 'Administrator level'
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='System administrators';
			
			-- Supervisions
			CREATE TABLE `supervisions` (
			  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Supervision ID #',
			  `username` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Username',
			  `courseId` int(11) NOT NULL COMMENT 'Course',
			  `courseName` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Course name',
			  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Supervision title',
			  `descriptionHtml` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Description',
			  `readingListHtml` text COLLATE utf8_unicode_ci COMMENT 'Reading list (optional)',
			  `studentsPerTimeslot` ENUM('1','2','3','4','5','6') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '3' COMMENT 'Students per timeslot',
			  `location` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Location(s)',
			  `length` int(11) NOT NULL COMMENT 'Length of time',
			  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Automatic timestamp',
			  `updatedAt` datetime NOT NULL COMMENT 'Updated at',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table of supervisions';
			
			-- Timeslots
			CREATE TABLE `timeslots` (
			  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `supervisionId` INT(11) NOT NULL COMMENT 'Supervision ID',
			  `startTime` datetime NOT NULL COMMENT 'Start datetime',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table of timeslots';
			
			-- Signups
			CREATE TABLE `signups` (
			  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `supervisionId` int(11) NOT NULL COMMENT 'Supervision ID',
			  `userId` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT 'User ID',
			  `userName` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'User name',
			  `startTime` datetime NOT NULL COMMENT 'Start datetime',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table of timeslots';
			
			-- Courses
			CREATE TABLE `courses` (
			  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `yearGroup` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Year group',
			  `courseNumber` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Course number',
			  `courseName` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Course name',
			  `available` int(1) NOT NULL DEFAULT '1' COMMENT 'Currently available?',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Courses';
		";
	}
	
	
	# Additional initialisation, pre-actions
	public function mainPreActions ()
	{
		# Determine if the user is staff
		$userIsStaffCallbackFunction = $this->settings['userIsStaffCallback'];
		$this->userIsStaff = ($this->user ? $userIsStaffCallbackFunction ($this->user) : false);
		
	}
	
	
	# Additional initialisation
	public function main ()
	{
		# Determine if the user is staff
		$userYeargroupCallbackFunction = $this->settings['userYeargroupCallback'];
		$this->userYeargroup = ($this->user ? $userYeargroupCallbackFunction ($this->user) : false);
		
	}
	
	
	# Welcome screen
	public function home ()
	{
		# Start the HTML
		$html = '';
		
		# List of supervisions
		$html .= "\n<h2>Sign up to a supervision</h2>";
		$html .= "\n<p>You can sign up to the following supervisions online:</p>";
		$html .= $this->supervisionsList ();
		
		# Give link for staff
		if ($this->userIsStaff) {
			$html .= "\n<br />";
			$html .= "\n<h2>Create supervision signup sheet</h2>";
			$html .= "\n<p>As a member of staff, you can <a href=\"{$this->baseUrl}/add/\" class=\"actions\"><img src=\"/images/icons/add.png\" alt=\"Add\" border=\"0\" /> create a supervision signup sheet</a>.</p>";
		}
		
		# Return the HTML
		echo $html;
	}
	
	
	# Function to list supervisions
	private function supervisionsList ()
	{
		# Get the supervisions
		$supervisions = $this->getSupervisions ();
		
		# Convert to HTML list
		$list = array ();
		foreach ($supervisions as $id => $supervision) {
			$list[$id] = "<a href=\"{$supervision['href']}\">" . htmlspecialchars ($supervision['courseName'] . ': ' . $supervision['title'] . ' (' . $supervision['username'] . ')') . '</a>';
		}
		$html = application::htmlUl ($list);
		
		# Return the HTML
		return $html;
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
	
	
	# Courses editing section, substantially delegated to the sinenomine editing component
	public function courses ($attributes = array (), $deny = false)
	{
		# Get the databinding attributes
		$dataBindingAttributes = array (
			'yearGroup' => array ('type' => 'select', 'values' => $this->settings['yearGroups'], ),		// NB: Strings must match response from userYeargroupCallback
		);
		
		# Define general sinenomine settings
		$sinenomineExtraSettings = array (
			'submitButtonPosition' => 'bottom',
			'int1ToCheckbox' => true,
		);
		
		# Delegate to the standard function for editing
		echo $this->editingTable ('courses', $dataBindingAttributes, 'ultimateform', false, $sinenomineExtraSettings);
	}
	
	
	# Supervision editing form
	private function supervisionForm ($supervision = array ())
	{
		# Start the HTML
		$html = '';
		
		# Get the courses
		$courses = $this->getCourses ();
		
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
			'data' => $supervision,
			'intelligence' => true,
			'exclude' => array ('username', 'courseName'),		// Fixed data fields, handled below
			'attributes' => array (
				'courseId' => array ('heading' => array (3 => 'Supervision details'), 'type' => 'select', 'values' => $courses, ),
				'studentsPerTimeslot' => array ('heading' => array (3 => 'Dates, times and locations'), ),
				'length' => array ('type' => 'select', 'values' => $this->settings['lengths'], 'default' => ($supervision ? $supervision['length'] : $this->settings['lengthDefault']), ),
			),
		));
		$form->input (array (
			'name' => 'timeslots',
			'title' => 'Timeslots (start time of each available supervision)',
			'expandable' => "\n",
			'required' => true,
			'default' => ($supervision ? implode ("\n", $supervision['timeslots']) : false),
			'placeholder' => 'YYYY-MM-DD hh:mm:ss',
		));
		if ($result = $form->process ($html)) {
			
			# Add in fixed data
			$result['username'] = $this->user;
			$result['updatedAt'] = 'NOW()';
			if ($supervision) {
				$result['id'] = $supervision['id'];
			}
			
			# Add in the course name so that this not dependent on a live feed which may change from year to year
			$coursesFlattened = application::flattenMultidimensionalArray ($courses);
			$result['courseName'] = $coursesFlattened[$result['courseId']];
			
			# Extract the timeslots for entering in the separate timeslot table
			$timeslots = explode ("\n", $result['timeslots']);
			unset ($result['timeslots']);
			
			# Insert the new supervision into the database
			$databaseAction = ($supervision ? 'update' : 'insert');
			$parameter4 = ($supervision ? array ('id' => $supervision['id']) : false);
			if (!$this->databaseConnection->{$databaseAction} ($this->settings['database'], $this->settings['table'], $result, $parameter4)) {
				$html .= "\n" . '<p class="warning">There was a problem ' . ($supervision ? 'updating' : 'creating') . ' the new supervision signup sheet.</p>';
				return $html;
			}
			
			# Get the supervision ID just inserted
			$supervisionId = ($supervision ? $supervision['id'] : $this->databaseConnection->getLatestId ());
			
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
			
			# If editing, clear out any timeslots that are no longer wanted
			if ($supervision) {
				if (!$this->databaseConnection->delete ($this->settings['database'], 'timeslots', array ('supervisionId' => $supervisionId))) {
					$html .= "\n" . '<p class="warning">There was a problem clearing out timeslots that are no longer wanted.</p>';
					return $html;
				}
			}
			
			# Insert the new timeslots
			if (!$this->databaseConnection->insertMany ($this->settings['database'], 'timeslots', $timeslotInserts)) {
				$html .= "\n" . '<p class="warning">There was a problem creating the new supervision signup sheet.</p>';
				return $html;
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
		
		# Enable editing by staff
		if ($this->userIsStaff) {
			
			# Determine if editing is requested
			$editingActions = array (
				'edit'		=> 'Edit supervision details',
				'clone'		=> 'Clone to new supervision',
				'delete'	=> 'Delete supervision details',
			);
			$do = (isSet ($_GET['do']) ? $_GET['do'] : false);
			if ($do) {
				
				# Validate
				if (!isSet ($editingActions[$do])) {
					$this->page404 ();
					return false;
				}
				
				# Perform editing action, restarting the HTML
				$function = $do . 'Supervision';	// e.g. editSupervision
				$html  = "\n<h2>{$editingActions[$do]}</h2>";
				$html .= $this->{$function} ($supervision);
				echo $html;
				return true;
			}
			
			# Show the edit button
			$html .= "\n<p><a href=\"{$this->baseUrl}/{$id}/edit.html\" class=\"actions right\"><img src=\"/images/icons/pencil.png\" alt=\"Edit\" border=\"0\" /> Edit</a></p>";
		}
		
		# Arrange signups by timeslot
		$signups = array ();
		foreach ($supervision['signups'] as $id => $signup) {
			$startTime = $signup['startTime'];
			$signups[$startTime][] = $signup;	// Indexed from 0
		}
		
		# Determine if the user has signed-up already
		$userHasSignedUp = false;
		foreach ($supervision['signups'] as $id => $signup) {
			if ($signup['userId'] == $this->user) {
				$userHasSignedUp = true;
				break;
			}
		}
		
		# Get the person name
		$userLookupData = camUniData::getLookupData ($supervision['username']);
		
		# Create the supervision page
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
		$html .= "\n<br />";
		$html .= "\n<h3>Time slots:</h3>";
		
		# Determine the posted slot
		if (isSet ($_POST['timeslot']) && is_array ($_POST['timeslot']) && count ($_POST['timeslot']) == 1) {
			$submittedId = key ($_POST['timeslot']);
			if (in_array ($submittedId, $supervision['timeslots'])) {
				if (!$this->addSignup ($supervision['id'], $this->user, $this->userName, $submittedId)) {
					$html .= "\n" . '<p class="warning">There was a problem registering the signup.</p>';
					echo $html;
					return false;
				}
				
				# Refresh the page
				$html .= application::sendHeader ('refresh', false, $redirectMessage = true);
				echo $html;
				return;
			}
		}
		
		# Arrange timeslots by date
		$timeslotsByDate = array ();
		foreach ($supervision['timeslots'] as $id => $startTime) {
			$date = date ('Y-m-d', strtotime ($startTime));
			$timeFormatted = date ('H:i', strtotime ($startTime)) . ' - ' . date ('H:i', strtotime ($startTime) + ($supervision['length'] * 60));
			$timeslotsByDate[$date][$id] = $timeFormatted;
		}
		
		# Determine today
		$today = date ('Y-m-d');
		
		# Create the timeslot buttons
		$html .= "\n\n<form class=\"timeslots\" name=\"timeslot\" action=\"\" method=\"post\">";
		$html .= "\n\n\t<table class=\"lines\">";
		$userSlotPassed = false;
		foreach ($timeslotsByDate as $date => $timeslotsForDate) {
			$editable = ($date > $today);
			$totalThisDate = count ($timeslotsForDate);
			$first = true;
			foreach ($timeslotsForDate as $id => $timeFormatted) {
				$indexValue = $supervision['timeslots'][$id];
				$html .= "\n\t\t<tr" . ($editable ? '' : ' class="uneditable"') . '>';
				if ($first) {
					$html .= "\n\t\t\t<td rowspan=\"{$totalThisDate}\"><strong>" . nl2br (date ("l,\njS F Y", strtotime ($date . ' 12:00:00'))) . ":</strong></h5>";
					$first = false;
				} else {
					$html .= "\n\t\t\t";
				}
				$html .= "\n\t\t\t<td>{$timeFormatted}:</td>";
				$startTime = $supervision['timeslots'][$id];
				$showButton = true;
				if (!$editable) {$showButton = false;}
				for ($i = 0; $i < $supervision['studentsPerTimeslot']; $i++) {
					$html .= "\n\t\t\t\t<td>";
					$slotTaken = (isSet ($signups[$startTime]) && isSet ($signups[$startTime][$i]));
					if ($slotTaken) {
						$signup = $signups[$startTime][$i];
						$html .= "<div class=\"timeslot " . ($signup['userId'] == $this->user ? 'me' : 'taken') . "\"><p>{$signup['userName']}<br /><span>{$signup['userId']}</span></p></div>";
						if ($signup['userId'] == $this->user) {
							$showButton = false;
							if (!$editable) {$userSlotPassed = true;}
						}
					} else {
						if ($showButton && !$userSlotPassed) {
							$label = ($userHasSignedUp ? 'Change to here' : 'Sign up');
							$html .= "<input type=\"submit\" name=\"timeslot[{$indexValue}]\" value=\"{$label}\" />";		// See multiple button solution using [] at: http://stackoverflow.com/a/34915274/180733
							$showButton = false;	// Only first in row show be clickable, so they fill up from the left
						} else {
							$html .= "<div class=\"timeslot available\"><p>" . ($editable ? 'Available' : '-') . '</p></div>';
						}
					}
					$html .= '</td>';
				}
				$html .= "\n\t\t</tr>";
			}
		}
		$html .= "\n\t</table>";
		$html .= "\n\n</form>";
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to edit a supervision
	private function editSupervision ($supervision)
	{
		# Run the form in editing mode
		$html = $this->supervisionForm ($supervision);
		
		# Return the HTML
		return $html;
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
		
		# Add the student signup data (which may be empty)
		$supervision['signups'] = $this->databaseConnection->select ($this->settings['database'], 'signups', array ('supervisionId' => $id), array ('id', 'userId', 'userName', 'startTime'), true, $orderBy = 'startTime');
		
		// application::dumpData ($supervision);
		
		# Return the collection
		return $supervision;
	}
	
	
	# Model function to get supervisions
	private function getSupervisions ()
	{
		# Obtain the supervision data
		$supervisions = $this->databaseConnection->select ($this->settings['database'], $this->settings['table']);
		
		# Add link to each
		foreach ($supervisions as $id => $supervision) {
			$supervisions[$id]['href'] = $this->baseUrl . '/' . $id . '/';
		}
		
		# Return the data
		return $supervisions;
	}
	
	
	# Model function to sign up a student
	private function addSignup ($supervisionId, $userId, $userName, $startTime)
	{
		# Assemble the data identity
		$entry = array (
			'supervisionId' => $supervisionId,
			'userId' => $this->user,
		);
		
		# Clear any existing entry for this user
		$this->databaseConnection->delete ($this->settings['database'], 'signups', $entry);
		
		# Add attributes
		$entry['userName'] = $userName;
		$entry['startTime'] = $startTime;
		
		# Insert the row
		return $this->databaseConnection->insert ($this->settings['database'], 'signups', $entry);
	}
	
	
	# Model function to get the courses
	private function getCourses ()
	{
		# Get the courses
		$data = $this->databaseConnection->select ($this->settings['database'], 'courses', array ('available' => '1'), array (), true, $orderBy = 'yearGroup, courseNumber, courseName');
		
		# Regroup as nested set
		$courses = array ();
		foreach ($data as $id => $course) {
			$yearGroup = $course['yearGroup'] . ':';
			$id = $course['id'];
			$courses[$yearGroup][$id] = ($course['courseNumber'] ? $course['courseNumber'] . ': ' : '') . $course['courseName'];
		}
		
		# Natsort each set
		foreach ($courses as $yearGroup => $set) {
			natsort ($set);
			$courses[$yearGroup] = $set;
		}
		
		# Return the courses
		return $courses;
	}
}

?>