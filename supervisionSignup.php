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
			'settingsTableExplodeTextarea' => true,
			'useCamUniLookup' => true,
			'emailDomain' => 'cam.ac.uk',
			'administrators' => true,
			'userIsStaffCallback' => 'userIsStaffCallback',		// Callback function
			'userYeargroupCallback' => 'userYeargroupCallback',	// Callback function
			'userNameCallback' => false,						// Callback function; useful if a better name source than Lookup (which tends only to have initials for forenames) is available
			'authentication' => true,
			'databaseStrictWhere' => true,
			'lengths' => array (30 => '30 minutes', 45 => '45 minutes', 60 => '1 hour', 90 => 'Hour and a half', 120 => 'Two hours', ),
			'lengthDefault' => 60,
			'yearGroups' => array ('Part IA', 'Part IB', 'Part II'),
			'organisationDescription' => 'the Department',
			'timeslotsWeeksAhead' => 14,
			'morningFirstHour' => 8,	// First hour that is in the morning; e.g. if set to 8, staff-entered time '8' would mean 8am rather than 8pm, and '7' would mean 7pm
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
			'my' => array (
				'description' => 'My supervisions',
				'url' => 'my/',
				'tab' => 'My supervisions',
				'icon' => 'asterisk_orange',
				'enableIf' => $this->userIsStaff,
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
			
			-- Settings
			CREATE TABLE IF NOT EXISTS `settings` (
			  `id` int(11) NOT NULL COMMENT 'Automatic key (ignored)' PRIMARY KEY,
			  `additionalSupervisors` text COLLATE utf8_unicode_ci COMMENT 'Additional supervisors (usernames, one per line)'
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Settings';
			INSERT INTO settings (id, additionalSupervisors) VALUES ('1', NULL);
			
			-- Supervisions
			CREATE TABLE `supervisions` (
			  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Supervision ID #',
			  `supervisor` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Supervisor username',
			  `supervisorName` VARCHAR(255) COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT 'Supervisor name',
			  `courseId` int(11) NOT NULL COMMENT 'Course',
			  `courseName` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Course name',
			  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Supervision title',
			  `descriptionHtml` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT 'Description',
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
			  `ordering` INT(1) NULL DEFAULT '5' COMMENT 'Ordering (1=first, 9=last)' AFTER `available`,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Courses';
		";
	}
	
	
	# Additional initialisation, pre-actions
	public function mainPreActions ()
	{
		# Determine if the user is staff from the callback function
		$userIsStaffCallbackFunction = $this->settings['userIsStaffCallback'];
		$this->userIsStaff = ($this->user ? $userIsStaffCallbackFunction ($this->user) : false);
		
		# Also enable additional staff users set in the database
		if ($this->user) {
			if (in_array ($this->user, $this->settings['additionalSupervisors'])) {
				$this->userIsStaff = true;
			}
		}
		
	}
	
	
	# Additional initialisation
	public function main ()
	{
		# Load required libraries
		require_once ('timedate.php');
		
		# Determine the full name of a user via callback if specified
		if ($this->settings['userNameCallback']) {
			$userNameCallback = $this->settings['userNameCallback'];
			if ($userName = $userNameCallback ($this->user)) {
				$this->userName = $userName;
			}
		}
		
		# Determine the year group of the user
		$userYeargroupCallbackFunction = $this->settings['userYeargroupCallback'];
		$this->userYeargroup = ($this->user ? $userYeargroupCallbackFunction ($this->user) : false);
		
		# Ensure the user is current student or staff (or admin)
		if (!$this->userYeargroup && !$this->userIsStaff && !$this->userIsAdministrator) {
			if ($this->action != 'feedback') {		// Unless on feedback page
				$html  = "\n<p>This system is only available to current students and staff of " . htmlspecialchars ($this->settings['organisationDescription']) . '.</p>';
				$html .= "\n<p>If you think you should have access, please <a href=\"{$this->baseUrl}/feedback.html\">contact us</a>.</p>";
				echo $html;
				return false;
			}
		}
		
	}
	
	
	# Welcome screen
	public function home ()
	{
		# Start the HTML
		$html = '';
		
		# Get the supervisions
		$supervisions = $this->getSupervisions ($this->userYeargroup);
		
		# List of supervisions
		$html .= "\n<h2>Sign up to a supervision</h2>";
		if ($supervisions) {
			$html .= "\n<p>You can sign up to the following supervisions online:</p>";
			$html .= $this->supervisionsList ($supervisions);
		} else {
			$html .= "\n<p>There are no supervisions available to sign up to yet.</p>";
		}
		
		# Give links for staff
		if ($this->userIsStaff) {
			$html .= "\n<br />";
			$html .= "\n<h2>Create supervision signup sheet</h2>";
			$html .= "\n<p>As a member of staff, you can <a href=\"{$this->baseUrl}/add/\" class=\"actions\"><img src=\"/images/icons/add.png\" alt=\"Add\" border=\"0\" /> Create a supervision signup sheet</a>.</p>";
			$html .= "\n<br />";
			$html .= "\n<h2>My supervisions</h2>";
			$html .= "\n<p>As a member of staff, you can <a href=\"{$this->baseUrl}/my/\" class=\"actions\"><img src=\"/images/icons/asterisk_orange.png\" alt=\"Add\" border=\"0\" /> View supervisions you are running</a>.</p>";
		}
		
		# Return the HTML
		echo $html;
	}
	
	
	# Function to list supervisions
	private function supervisionsList ($supervisions, $showSupervisor = true)
	{
		# Start the HTML
		$html  = '';
		
		# Rearrange by yeargroup
		$supervisionsByYeargroup = $this->arrangeByYeargroup ($supervisions);
		
		# Convert to HTML list
		foreach ($supervisionsByYeargroup as $yeargroup => $supervisionsByCourse) {
			$html .= "\n\n<h3>{$yeargroup}:</h3>";
			
			# Start a table of courses
			$table = array ();
			
			# Show each course
			foreach ($supervisionsByCourse as $courseDescription => $supervisions) {
				$key = "<h4>{$courseDescription}:</h4>";
				$list = array ();
				foreach ($supervisions as $id => $supervision) {
					$list[$id] = "<a href=\"{$supervision['href']}\"" . ($supervision['hasFinished'] ? ' class="finished"' : '') . '>'. htmlspecialchars ($supervision['title']) . ($showSupervisor ? ' (' . $supervision['supervisorName'] . ')' : '') . '</a>';
				}
				$table[$key] = application::htmlUl ($list, 3);
			}
			
			# Construct the table
			$html .= application::htmlTableKeyed ($table, array (), true, 'lines graybox listing', $allowHtml = true, $showColons = false);
		}
		
		# Wrap in a div
		$html = "\n\n<div id=\"listing\">" . $html . "\n\n</div>";
		
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
	
	
	# Function to list a user's supervisions
	public function my ()
	{
		# Start the HTML
		$html = '';
		
		# Get the supervisions
		$supervisionsSupervising = $this->getSupervisions (false, $this->user);
		
		# List the supervisions for this user
		if ($supervisionsSupervising) {
			$html .= "\n<p>You are running the supervisions listed below.</p>";
			$html .= "\n<p>You can view the student signups, or edit/delete a supervision, on each page.</p>";
			$html .= $this->supervisionsList ($supervisionsSupervising, false);
		} else {
			$html .= "\n<p>There are none.</p>";
			$html .= "\n<p>You can <a href=\"{$this->baseUrl}/add/\">create a new supervision signup sheet</a>.</p>";
		}
		
		# Return the HTML
		echo $html;
	}
	
	
	# Courses editing section, substantially delegated to the sinenomine editing component
	public function courses ($attributes = array (), $deny = false)
	{
		# Get the databinding attributes
		$dataBindingAttributes = array (
			'yearGroup' => array ('type' => 'select', 'values' => $this->settings['yearGroups'], ),		// NB: Strings must match response from userYeargroupCallback
			'ordering' => array ('type' => 'select', 'values' => range (1, 9), ),
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
	private function supervisionForm ($supervision = array (), $editMode = false)
	{
		# Start the HTML
		$html = '';
		
		# Get the courses
		$courses = $this->getCourses ();
		
		# Create the timeslots, using the Mondays from the start of the week for the current date (for a new supervision) or the creation date (editing an existing one)
		$allDays = $this->calculateTimeslotDates ($supervision);
		
		# Compile the timeslots template HTML, and obtain the timeslot fields created
		$fieldnamePrefix = 'timeslots_';
		$dateTextFormat = 'D jS M';
		$timeslotsHtml = $this->timeslotsHtml ($allDays, $fieldnamePrefix, $dateTextFormat, $timeslotsFields /* returned by reference */);
		
		# If editing, parse existing timeslots to the textarea format; clone mode only clones properties, not timeslots
		$timeslotsDefaults = $this->parseExistingTimeslots (($editMode ? $supervision : array ()));
		
		# If editing, obtain a list of timeslots already chosen by users, which therefore cannot be removed
		$alreadyChosenSignups = $this->signupsByTimeslot (($editMode ? $supervision : array ()), true);
		
		# Databind a form
		$form = new form (array (
			'div' => false,
			'displayRestrictions' => false,
			'formCompleteText' => false,
			'databaseConnection' => $this->databaseConnection,
			'richtextEditorToolbarSet' => 'BasicNoLinks',
			'richtextWidth' => 650,
			'richtextHeight' => 250,
			'cols' => 80,
			'autofocus' => true,
			'unsavedDataProtection' => true,
			'display' => 'template',
			'displayTemplate' => $this->formTemplate ($timeslotsHtml),
		));
		$form->dataBinding (array (
			'database' => $this->settings['database'],
			'table' => $this->settings['table'],
			'data' => $supervision,
			'intelligence' => true,
			'exclude' => array ('id', 'supervisor', 'supervisorName', 'courseName'),		// Fixed data fields, handled below
			'attributes' => array (
				'courseId' => array ('type' => 'select', 'values' => $courses, ),
				'length' => array ('type' => 'select', 'values' => $this->settings['lengths'], 'default' => ($supervision ? $supervision['length'] : $this->settings['lengthDefault']), ),
			),
		));
		
		# Add a widget for each timeslot
		foreach ($allDays as $weekStartUnixtime => $daysOfWeek) {
			foreach ($daysOfWeek as $dayUnixtime => $dayYmd) {
				$dayNumber = date ('N', $dayUnixtime);	// Monday is 1
				$form->textarea (array (
					'name' => $timeslotsFields[$dayUnixtime],
					'title' => date ('Y-m-d', $dayUnixtime),
					'cols' => ($dayNumber > 5 ? 9 : 11),	// Less space for Saturday/Sunday as unlikely to be used
					'rows' => 5,
					'default' => (isSet ($timeslotsDefaults[$dayYmd]) ? $timeslotsDefaults[$dayYmd] : false),
				));
			}
		}
		
		# Check the start times and obtain the list
		$startTimesPerDate = array ();
		if ($unfinalisedData = $form->getUnfinalisedData ()) {
			
			# Check start times via parser, by checking each field
			$timeslots = application::arrayFields ($unfinalisedData, $timeslotsFields);
			foreach ($timeslots as $fieldname => $times) {
				
				# Skip if no data submitted
				if (!$times) {continue;}
				
				# Remove the fieldname prefix to create the date
				$date = str_replace ($fieldnamePrefix, '', $fieldname);	// e.g. 2016-11-08
				
				# Parse out the text block to start times
				if (!$startTimesPerDate[$date] = $this->parseStartTimes ($times, $date, $dateTextFormat, $errorHtml /* returned by reference */)) {
					$form->registerProblem ('starttimeparsefailure', $errorHtml, $fieldname);
				}
				
				# Add constraint that number of slots cannot be reduced if that number is filled
				if ($unfinalisedData['studentsPerTimeslot']) {
					if ($editMode) {
						$greatestTotalSignups = 0;
						foreach ($alreadyChosenSignups as $date) {
							foreach ($date as $timeslot => $signupsThisSlot) {
								$totalSignups = count ($signupsThisSlot);
								$greatestTotalSignups = max ($greatestTotalSignups, $totalSignups);
							}
						}
						if ($unfinalisedData['studentsPerTimeslot'] < $greatestTotalSignups) {
							$form->registerProblem ('timeslotloss', "You cannot reduce to {$unfinalisedData['studentsPerTimeslot']} students per timeslot because there is already a timeslot with {$greatestTotalSignups} existing student signups.");
						}
					}
				}
			}
			
			# Ensure that at least one timeslot has been created
			if (!$startTimesPerDate) {
				$form->registerProblem ('notimeslots', 'No timeslots have been set.');
			}
			
			# Prevent deletion of slots that have already been chosen by a student
			if ($missingAlreadyChosen = $this->missingAlreadyChosen ($alreadyChosenSignups, $startTimesPerDate, $dateTextFormat)) {
				$form->registerProblem ('missingchosen', 'Some of the timeslots that you attempted to remove already have signups: <em>' . implode ('</em>, <em>', $missingAlreadyChosen) . '</em>, so you need to be reinstate those times in the timeslots list below.');
			}
		}
		
		# Process the form
		if ($result = $form->process ($html)) {
			
			# Add in fixed data
			if (!$editMode) {
				$result['supervisor'] = $this->user;
				$result['supervisorName'] = $this->userName;
			}
			$result['updatedAt'] = 'NOW()';
			if ($editMode) {
				$result['id'] = $supervision['id'];
			}
			
			# Strip links (and other HTML), so that links can reliably be rendered dynamically (avoiding auto-linking of a link tag)
			$result['descriptionHtml'] = strip_tags ($result['descriptionHtml'], '<p><br><strong><em><u><ul><ol><li>');
			
			# Add in the course name so that this not dependent on a live feed which may change from year to year
			$coursesFlattened = application::flattenMultidimensionalArray ($courses);
			$result['courseName'] = $coursesFlattened[$result['courseId']];
			
			# Extract the timeslots for entering in the separate timeslot table, and remove from the main insert
			foreach ($result as $field => $value) {
				if (in_array ($field, $timeslotsFields)) {
					unset ($result[$field]);
				}
			}
			
			# Insert the new supervision into the database
			$databaseAction = ($editMode ? 'update' : 'insert');
			$parameter4 = ($editMode ? array ('id' => $supervision['id']) : false);
			if (!$this->databaseConnection->{$databaseAction} ($this->settings['database'], $this->settings['table'], $result, $parameter4)) {
				$html .= "\n" . '<p class="warning">There was a problem ' . ($editMode ? 'updating' : 'creating') . ' the new supervision signup sheet.</p>';
				return $html;
			}
			
			# Get the supervision ID just inserted
			$supervisionId = ($editMode ? $supervision['id'] : $this->databaseConnection->getLatestId ());
			
			# Construct the timeslot inserts
			$timeslotInserts = array ();
			foreach ($startTimesPerDate as $date => $startTimes) {
				foreach ($startTimes as $startTime) {
					$timeslotInserts[] = array (
						'supervisionId' => $supervisionId,
						'startTime' => $startTime,
					);
				}
			}
			
			# If editing, clear out any timeslots that are no longer wanted
			if ($editMode) {
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
			
			# Determine the page URL
			$redirectTo = $this->baseUrl . '/' . $supervisionId . '/';
			
			# Mail the user (except when editing)
			if (!$editMode) {
				$to = $this->user . '@' . $this->settings['emailDomain'];
				$subject = "Supervision signup sheet created: {$result['title']} ({$result['courseName']})";
				$message = "\nThis e-mail confirms that you have created an online supervision signup sheet.";
				$message .= "\n\nYou can view student signups, or edit the details, at:\n\n{$_SERVER['_SITE_URL']}{$redirectTo}";
				$message .= "\n\nPlease note that you will not receive any further e-mails about this supervision signup sheet.";
				$extraHeaders  = 'From: Webserver <' . $this->settings['webmaster'] . '>';
				$extraHeaders .= "\r\n" . 'Bcc: ' . $this->settings['administratorEmail'];
				application::utf8Mail ($to, $subject, wordwrap ($message), $extraHeaders);
			}
			
			# Redirect the user
			$html .= application::sendHeader (302, $redirectTo, true);
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to determine the timeslots based on a start time
	private function calculateTimeslotDates ($supervision)
	{
		# Start from either today or, if there is a supervision being edited, the creation date
		$timestamp = ($supervision ? strtotime ($supervision['createdAt']) : false);
		
		# Get the Mondays from the start timestamp
		$mondaysUnixtime = timedate::getMondays ($this->settings['timeslotsWeeksAhead'], false, true, $timestamp);
		
		# Calculate the days for each week
		$allDays = array ();
		foreach ($mondaysUnixtime as $weekStartUnixtime) {
			for ($days = 0; $days < 7; $days++) {
				$dayUnixtime = $weekStartUnixtime + ($days * 60 * 60 * 24);
				$dayYmd = date ('Y-m-d', $dayUnixtime);
				$allDays[$weekStartUnixtime][$dayUnixtime] = $dayYmd;
			}
		}
		
		# Return the dates
		return $allDays;
	}
	
	
	# Function to create the timeslots HTML
	private function timeslotsHtml ($allDays, $fieldnamePrefix, $dateTextFormat, &$timeslotsFields = array ())
	{
		# Start the table
		$html  = "\n\t\t\t\t\t\t" . '<table class="border">';
		
		# Add the header row
		$html .= "\n\t\t\t\t\t\t\t" . '<tr>';
		$html .= "\n\t\t\t\t\t\t\t\t" . '<td></td>';
		$days = array ('Monday', 'Tuesday', 'Wednesday','Thursday','Friday', 'Saturday', 'Sunday');
		foreach ($days as $day) {
			$html .= "\n\t\t\t\t\t\t\t\t" . '<th class="' . strtolower ($day) . '">' . $day . '</th>';
		}
		$html .= "\n\t\t\t\t\t\t\t" . '</tr>';
		
		# Create a list of the fields, to be passed back by reference
		$timeslotsFields = array ();
		
		# Start each week
		foreach ($allDays as $weekStartUnixtime => $daysOfWeek) {
			$html .= "\n\t\t\t\t\t\t\t" . '<tr>';
			$html .= "\n\t\t\t\t\t\t\t\t" . '<td class="comment">Start times, e.g. :<br /><br /><span class="small">11<br />12<br />1.30</span></td>';
			
			# Add each day, saving the fieldname
			foreach ($daysOfWeek as $dayUnixtime => $dayYmd) {
				$html .= "\n\t\t\t\t\t\t\t\t" . '<td class="' . strtolower (date ('l', $dayUnixtime)) . '">';
				$html .= date ($dateTextFormat, $dayUnixtime) . ':<br />';
				$timeslotsFields[$dayUnixtime] = $fieldnamePrefix . $dayYmd;
				$html .= '{' . $timeslotsFields[$dayUnixtime] . '}';
				$html .= '</td>';
			}
			$html .= "\n\t\t\t\t\t\t\t" . '</tr>';
		}
		$html .= "\n\t\t\t\t\t\t" . '</table>';
		
		# Return the HTML
		return $html;
	}
	
	
	# Helper function to parse existing timeslots to the textarea format
	private function parseExistingTimeslots ($supervision)
	{
		# End if no existing supervision
		if (!$supervision) {return array ();}
		
		# Group as date to list of simplified times
		$timeslotsExistingByDate = array ();
		foreach ($supervision['timeslots'] as $timeslot) {
			list ($date, $time) = explode (' ', $timeslot, 2);
			$timeslotsExistingByDate[$date][] = timedate::simplifyTime ($time);
		}
		
		# Implode each date's entries
		$timeslotsDefaults = array ();
		foreach ($timeslotsExistingByDate as $date => $timeslotsExisting) {
			$timeslotsDefaults[$date] = implode ("\n", $timeslotsExisting);
		}
		
		# Return the list
		return $timeslotsDefaults;
	}
	
	
	# Form template
	private function formTemplate ($timeslotsHtml)
	{
		# Assemble the page template
		$html = "
			
			{[[PROBLEMS]]}
			
			<table class=\"lines setdetails\">
				
				<tr>
					<td colspan=\"2\"><h3>Supervision details</h3></td>
				</tr>
				<tr>
					<td>Course: *</td>
					<td>{courseId}</td>
				</tr>
				<tr>
					<td>Supervision title: *</td>
					<td>{title}</td>
				</tr>
				<tr>
					<td>Description (optional):<br /><br />(NB: Web addresses will automatically become links.)</td>
					<td>{descriptionHtml}</td>
				</tr>
				
				<tr>
					<td colspan=\"2\"><h3>Supervision format</h3></td>
				</tr>
				<tr>
					<td>Students per timeslot: *</td>
					<td>{studentsPerTimeslot}</td>
				</tr>
				<tr>
					<td>Location(s): *</td>
					<td>{location}</td>
				</tr>
				<tr>
					<td>Length of time: *</td>
					<td>{length}</td>
				</tr>
				
				<tr>
					<td colspan=\"2\">
						<h3>Timeslots</h3>
						<p><img src=\"/images/icons/information.png\" class=\"icon\" /> Enter each start time, one per line, on the relevant days, as per the example at the start of each line:</p>
						{$timeslotsHtml}
					</td>
				</tr>
				
				<tr>
					<td></td>
					<td>{[[SUBMIT]]}</td>
				</tr>
			</table>
		";
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to parse out the start times
	private function parseStartTimes ($times, $date, $dateTextFormat, &$errorHtml = false)
	{
		# Extract the times list as an array
		$times = application::textareaToList ($times);
		
		# Create a date description, for use in the event of an error message
		$dateDescription = date ($dateTextFormat, strtotime ($date . ' 12:00:00'));
		
		# Parse string to SQL time format
		foreach ($times as $index => $time) {
			if (!$parsedTime = timedate::parseTime ($time)) {		// e.g. 02:30:00
				$errorHtml = "In the timeslots field for {$dateDescription}, the time '<em>" . htmlspecialchars ($time) . "</em>' appears to be invalid.";
				return false;
			}
			$times[$index] = $parsedTime;
		}
		
		# Compile as date and time
		$startTimes = array ();
		foreach ($times as $index => $time) {
			$startTimes[$index] = $date . ' ' . $time;
		}
		
		# Convert mornings to 24 hours
		foreach ($times as $index => $time) {
			list ($hours, $minutes, $seconds) = explode (':', $time, 3);
			if ($hours < $this->settings['morningFirstHour']) {
				$startTimes[$index] = date ('Y-m-d H:i:s', strtotime ($startTimes[$index] . ' + 12 hours'));
			}
		}
		
		# Ensure there are no duplicate values
		$startTimes = array_unique ($startTimes);
		if (count ($times) != count ($startTimes)) {
			$errorHtml = "In the timeslots field for {$dateDescription}, the list of times is not unique.";
			return false;
		}
		
		# Return the list
		return $startTimes;
	}
	
	
	# Function to detect cases of deletion of timeslot(s) that a student has already chosen
	private function missingAlreadyChosen ($alreadyChosenSignups, $startTimesPerDate, $dateTextFormat)
	{
		# Start a list of missing already-chosen signups
		$missingAlreadyChosen = array ();
		
		# Loop through the existing ones as the comparator, as the whole date may have been deleted, rather than the other way round
		foreach ($alreadyChosenSignups as $dateChosen => $signupsByDatetime) {
			foreach ($signupsByDatetime as $datetimeChosen => $signup) {
				
				# If the date is missing, or the time within that date, is missing, register it
				if (!isSet ($startTimesPerDate[$dateChosen]) || !in_array ($datetimeChosen, $startTimesPerDate[$dateChosen])) {
					$date = date ($dateTextFormat, strtotime ($datetimeChosen));
					$time = timedate::simplifyTime (date ('H:i:s', strtotime ($datetimeChosen)));
					$missingAlreadyChosen[$date][] = $time;
				}
			}
		}
		
		# Compile to a string, in the format date (time1, time2, ...)
		foreach ($missingAlreadyChosen as $date => $times) {
			$missingAlreadyChosen[$date] = $date . ' (' . implode (', ', $times) . ')';
		}
		
		# Return the list, indexed by date, each containing a compiled list
		return $missingAlreadyChosen;
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
		
		# If the user is a student, require a match on yeargroup, for privacy reasons
		if (!$this->userIsStaff && !$this->userIsAdministrator) {
			if ($supervision['yearGroup'] != $this->userYeargroup) {
				$this->page404 ();
				return;
			}
		}
		
		# Determine editing rights
		$userHasEditRights = ($supervision['supervisor'] == $this->user);
		
		# Enable editing by the user
		if ($this->userIsStaff) {
			
			# Determine if editing is requested
			$editingActions = array ();
			if ($userHasEditRights) {
				$editingActions = array (
					'edit'		=> 'Edit supervision details',
					'delete'	=> 'Delete supervision details',
					'clone'		=> 'Clone to new supervision',
				);
			}
			$do = (isSet ($_GET['do']) ? $_GET['do'] : false);
			if ($do) {
				
				# Validate action and rights
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
			
			# Show the edit and delete buttons
			if ($userHasEditRights) {
				$html .= "\n<ul class=\"actions right\">";
				$html .= "\n\t<li><a href=\"{$this->baseUrl}/{$id}/edit.html\"><img src=\"/images/icons/pencil.png\" alt=\"Edit\" border=\"0\" /> Edit</a></li>";
				$html .= "\n\t<li><a href=\"{$this->baseUrl}/{$id}/delete.html\"><img src=\"/images/icons/bin.png\" alt=\"Edit\" border=\"0\" /> Delete &hellip;</a></li>";
				$html .= "\n\t<li><a href=\"{$this->baseUrl}/{$id}/clone.html\"><img src=\"/images/icons/page_copy.png\" alt=\"Edit\" border=\"0\" /> Clone</a></li>";
				$html .= "\n</ul>";
			}
		}
		
		# Add title
		$html .= "\n<h2>Sign up to a supervision</h2>";
		
		# Show the supervision
		$html .= $this->showSupervision ($supervision);
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to show a supervision
	private function showSupervision ($supervision)
	{
		# Start the HTML
		$html = '';
		
		# Arrange signups by timeslot
		$signups = $this->signupsByTimeslot ($supervision);
		
		# Determine if the user has signed-up already
		$userSignup = false;
		foreach ($supervision['signups'] as $id => $signup) {
			if ($signup['userId'] == $this->user) {
				$userSignup = $signup;
				break;
			}
		}
		
		# Add iCal export button
		$icalHtml = '';
		if ($userSignup) {
			$icalHtml = "\n" . "<p><a href=\"{$this->baseUrl}/{$supervision['id']}/supervision{$supervision['id']}.ics\">" . '<img src="/images/icons/extras/ical.gif" alt="iCal" title="iCal output - export to your calendar" class="right" /></a></p>';
		}
		
		# Serve iCal feed if required
		if ($userSignup) {
			if (isSet ($_GET['ical'])) {
				$this->iCal ($supervision, $userSignup['startTime']);
			}
		}
		
		# Create the supervision page
		$html .= "\n<h3>" . htmlspecialchars ($supervision['title']) . '</h3>';
		$html .= $icalHtml;
		$html .= "\n<p>";
		$html .= "\n\tYear group: <strong>" . htmlspecialchars ($supervision['yearGroup']) . '</strong><br />';
		$html .= "\n\tCourse: <strong>" . htmlspecialchars ($supervision['courseName']) . '</strong>';
		$html .= "\n</p>";
		$html .= "\n<p>With: <strong>" . htmlspecialchars ($supervision['supervisorName']) . ' &lt;' . $supervision['supervisor'] . '&gt;' . '</strong></p>';
		if ($supervision['descriptionHtml']) {
			$html .= "\n<h4>Description:</h4>";
			$html .= "\n<div class=\"graybox\">";
			$html .= "\n" . application::makeClickableLinks ($supervision['descriptionHtml']);
			$html .= "\n</div>";
		}
		$html .= "\n<br />";
		$html .= "\n<h3 id=\"timeslots\">Time slots:</h3>";
		
		# Add the timeslot if required, determining the posted slot
		if (isSet ($_POST['timeslot']) && is_array ($_POST['timeslot']) && count ($_POST['timeslot']) == 1) {
			$startTime = key ($_POST['timeslot']);
			if (in_array ($startTime, $supervision['timeslots'])) {
				if (!$this->addSignup ($supervision['id'], $startTime, $this->user, $this->userName, $error /* returned by reference */)) {
					$html .= "\n<p class=\"warning\">{$error}</p>";
					return $html;
				}
				
				# Refresh the page
				$html .= application::sendHeader ('refresh', false, $redirectMessage = true);
				return $html;
			}
		}
		
		# Delete the timeslot if required
		if (isSet ($_POST['delete']) && is_array ($_POST['delete']) && count ($_POST['delete']) == 1) {
			$submittedToken = key ($_POST['delete']);
			if (substr_count ($submittedToken, ',')) {
				list ($startTime, $userId) = explode (',', $submittedToken, 2);
				if (in_array ($startTime, $supervision['timeslots'])) {
					if (!$this->deleteSignup ($supervision['id'], $startTime, $userId, $error /* returned by reference */)) {
						$html .= "\n<p class=\"warning\">{$error}</p>";
						return $html;
					}
					
					# Refresh the page
					$html .= application::sendHeader ('refresh', false, $redirectMessage = true);
					return $html;
				}
			}
		}
		
		# Arrange timeslots by date
		$timeslotsByDate = array ();
		foreach ($supervision['timeslots'] as $id => $startTime) {
			$date = date ('Y-m-d', strtotime ($startTime));
			$timeFormatted = '<span>' . timedate::simplifyTime (date ('H:i:s', strtotime ($startTime))) . '</span> -&nbsp;' . timedate::simplifyTime (date ('H:i:s', strtotime ($startTime) + ($supervision['length'] * 60)));
			$timeslotsByDate[$date][$id] = $timeFormatted;
		}
		
		# Determine today
		$today = date ('Y-m-d');
		
		# Define a set of abbreviations for days and months, so that "January" can be shown as "Jan" using CSS for mobile purposes
		$abbreviations = $this->getDaysMonthsAbbreviated ();
		
		# Surround the word part after the abbreviations with spans, e.g. Jan<span>uary</span>
		foreach ($abbreviations as $datePeriod => $datePeriodAbbreviated) {
			$abbreviations[$datePeriod] = preg_replace ("/^({$datePeriodAbbreviated})(.+)$/", '$1<span class="abbreviation">$2</span>', $datePeriod);
		}
		
		# Create the timeslot buttons
		$formTarget = $_SERVER['_PAGE_URL'] . '#timeslots';
		$html .= "\n\n<form class=\"timeslots\" name=\"timeslot\" action=\"" . htmlspecialchars ($formTarget) . "\" method=\"post\">";
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
					$date = nl2br (date ("l,\njS F Y", strtotime ($date . ' 12:00:00')));
					$html .= "\n\t\t\t<td class=\"date\" rowspan=\"{$totalThisDate}\">" . strtr ($date, $abbreviations) . ":</h5>";
					$first = false;
				} else {
					$html .= "\n\t\t\t";
				}
				$html .= "\n\t\t\t<td class=\"time\">{$timeFormatted}:</td>";
				$startTime = $supervision['timeslots'][$id];
				$showButton = true;
				if (!$editable) {$showButton = false;}
				$html .= "\n\t\t\t<td>";
				for ($i = 0; $i < $supervision['studentsPerTimeslot']; $i++) {
					$html .= "\n\t\t\t\t";
					$slotTaken = (isSet ($signups[$startTime]) && isSet ($signups[$startTime][$i]));
					if ($slotTaken) {
						$signup = $signups[$startTime][$i];
						$removeHtml = '';
						if ($signup['userId'] == $this->user || $this->userIsAdministrator) {
							$removeHtml = '<div class="delete"><input type="submit" name="delete[' . $indexValue . ',' . $signup['userId'] . ']" value="" onclick="return confirm(\'Are you sure?\');"></div>';	// See: http://stackoverflow.com/a/1193338/180733
						}
						$html .= "<div class=\"timeslot " . ($signup['userId'] == $this->user ? 'me' : 'taken') . "\">{$removeHtml}<p>{$signup['userName']}<br /><span>{$signup['userId']}</span></p></div>";
						if ($signup['userId'] == $this->user) {
							$showButton = false;
							if (!$editable) {$userSlotPassed = true;}
						}
					} else {
						if ($showButton && !$userSlotPassed) {
							$label = ($userSignup ? 'Change to here' : 'Sign up');
							$html .= "<input type=\"submit\" name=\"timeslot[{$indexValue}]\" value=\"{$label}\" />";		// See multiple button solution using [] at: http://stackoverflow.com/a/34915274/180733
							$showButton = false;	// Only first in row show be clickable, so they fill up from the left
						} else {
							$html .= "<div class=\"timeslot available\"><p>" . ($editable ? 'Available' : '-') . '</p></div>';
						}
					}
				}
				$html .= "\n\t\t\t</td>";
				$html .= "\n\t\t</tr>";
			}
		}
		$html .= "\n\t</table>";
		$html .= "\n\n</form>";
		
		# Return the HTML
		return $html;
	}
	
	
	# Helper function to get an array of days and months and their abbrevations
	private function getDaysMonthsAbbreviated ()
	{
		# Get days, e.g. Monday => Mon, Tuesday => Tue
		$days = array ();
		for ($i = 0; $i < 7; $i++) {
			$timestamp = strtotime ("+{$i} days", strtotime ('next Monday'));	// Any Monday
			$dayName = strftime ('%A', $timestamp);	// Locale-aware version of date()
			$dayAbbreviated = strftime ('%a', $timestamp);
			$days[$dayName] = $dayAbbreviated;
		}
		
		# Get months
		$calendar = cal_info (CAL_GREGORIAN);	// Locale-aware
		$months = array_combine ($calendar['months'], $calendar['abbrevmonths']);
		
		# Combine
		$abbreviations = array_merge ($days, $months);
		
		# Return the list
		return $abbreviations;
	}
	
	
	# Helper function to arrange existing signups by timeslot
	private function signupsByTimeslot ($supervision, $nestByDate = false)
	{
		# Not relevant if no existing supervision supplied
		if (!$supervision) {return array ();}
		
		# Filter, arranging by date
		$signups = array ();
		foreach ($supervision['signups'] as $id => $signup) {
			$startTime = $signup['startTime'];
			if ($nestByDate) {
				list ($date, $time) = explode (' ', $signup['startTime'], 2);
				$signups[$date][$startTime][] = $signup;	// Indexed from 0
			} else {
				$signups[$startTime][] = $signup;	// Indexed from 0
			}
		}
		
		# Return the list
		return $signups;
	}
	
	
	# Function to edit a supervision
	private function editSupervision ($supervision)
	{
		# Run the form in editing mode
		$html = $this->supervisionForm ($supervision, true);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to delete a supervision
	private function deleteSupervision ($supervision)
	{
		# Start the HTML
		$html = '';
		
		# Obtain confirmation from the user
		$message = '<strong>Are you sure you want to delete this supervision below? The data cannot be retrieved later.</strong>';
		$confirmation = 'Yes, delete';
		if ($this->areYouSure ($message, $confirmation, $html)) {
			
			# Do the deletion
			if (!$this->doSupervisionDeletion ($supervision['id'], $error)) {
				$html = "\n<p class=\"warning\">{$error}</p>";
				return $html;
			}
			
			# Confirm the success
			$html = "<p>{$this->tick} The supervision was deleted.</p>";
			
			# Return the HTML
			return $html;
		}
		
		# Show the supervision
		$html .= $this->showSupervision ($supervision);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to clone a supervision
	private function cloneSupervision ($supervision)
	{
		# Remove the ID
		unset ($supervision['id']);
		
		# Run the form with the details supplied
		$html = $this->supervisionForm ($supervision);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to get data for a single supervision
	private function getSupervision ($id)
	{
		# Obtain the supervision data or end
		$query = "SELECT
				{$this->settings['table']}.*,
				courses.yearGroup
			FROM {$this->settings['database']}.{$this->settings['table']}
			JOIN courses ON {$this->settings['table']}.courseId = courses.id
			WHERE {$this->settings['table']}.id = :id
		;";
		if (!$supervision = $this->databaseConnection->getOne ($query, "{$this->settings['database']}.{$this->settings['table']}", true, array ('id' => $id))) {
			return false;
		}
		
		# Add the timeslot data or end
		if (!$supervision['timeslots'] = $this->databaseConnection->selectPairs ($this->settings['database'], 'timeslots', array ('supervisionId' => $id), array ('id', 'startTime'), true, $orderBy = 'startTime')) {
			return false;
		}
		
		# Add the student signup data (which may be empty)
		$supervision['signups'] = $this->databaseConnection->select ($this->settings['database'], 'signups', array ('supervisionId' => $id), array ('id', 'userId', 'userName', 'startTime'), true, $orderBy = 'startTime, id');
		
		// application::dumpData ($supervision);
		
		# Return the collection
		return $supervision;
	}
	
	
	# Model function to get supervisions, arranged hierarchically
	private function getSupervisions ($yeargroup = false, $supervisor = false)
	{
		# Add constraints if required
		$preparedStatementValues = array ();
		if ($yeargroup) {
			$preparedStatementValues['yearGroup'] = $yeargroup;
		}
		if ($supervisor) {
			$preparedStatementValues['supervisor'] = $supervisor;
		}
		
		# Obtain the supervision data
		$query = "SELECT
				{$this->settings['table']}.id,
				supervisor,
				supervisorName,
				title,
				courseId,
				courses.yearGroup,
				courses.courseNumber,
				courses.courseName,
				DATE(MIN(timeslots.startTime)) AS dateFrom,
				DATE(MAX(timeslots.startTime)) AS dateUntil,
				IF ( DATE(NOW()) > DATE(MAX(timeslots.startTime)) , 1, '') AS hasFinished
			FROM {$this->settings['database']}.{$this->settings['table']}
			JOIN courses ON {$this->settings['table']}.courseId = courses.id
			LEFT JOIN timeslots ON supervisions.id = timeslots.supervisionId
			" . ($yeargroup ? 'WHERE yearGroup = :yearGroup' : '') . "
			" . ($supervisor ? 'WHERE supervisor = :supervisor' : '') . "
			GROUP BY supervisions.id
			ORDER BY courses.yearGroup, id
		;";
		$supervisions = $this->databaseConnection->getData ($query, "{$this->settings['database']}.{$this->settings['table']}", true, $preparedStatementValues);
		
		# Add link to each
		foreach ($supervisions as $id => $supervision) {
			$supervisions[$id]['href'] = $this->baseUrl . '/' . $id . '/';
		}
		
		# Return the supervisions
		return $supervisions;
	}
	
	
	# Helper function to rearrange supervisions by yeargroup
	private function arrangeByYeargroup ($supervisions)
	{
		# Regroup by yeargroup
		$supervisionsByYeargroup = application::regroup ($supervisions, 'yearGroup');
		
		# Regroup by course within each yeargroup
		foreach ($supervisionsByYeargroup as $yeargroup => $supervisions) {
			
			# Add each supervision
			$supervisionsThisYeargroup = array ();
			foreach ($supervisions as $id => $supervision) {
				$courseDescription = htmlspecialchars (($supervision['courseNumber'] ? 'Paper ' . $supervision['courseNumber'] . ': ' : '') . $supervision['courseName']);
				$supervisionsThisYeargroup[$courseDescription][$id] = $supervision;
			}
			
			# Natsort on the course name (i.e. the key)
			#!# Need to consider the problem of dealing with courses with no numbering
			array_multisort (array_keys ($supervisionsThisYeargroup), SORT_NATURAL, $supervisionsThisYeargroup);	// See: http://stackoverflow.com/a/20431495/180733
			
			# Replace the list
			$supervisionsByYeargroup[$yeargroup] = $supervisionsThisYeargroup;
		}
		
		# Return the data
		return $supervisionsByYeargroup;
	}
	
	
	# Model function to sign up a student
	private function addSignup ($supervisionId, $startTime, $userId /* assumed trusted */, $userName, &$error = false)
	{
		# Assemble the data identity
		$entry = array (
			'supervisionId' => $supervisionId,
			'userId' => $userId,
		);
		
		# Clear any existing entry for this user
		$this->databaseConnection->delete ($this->settings['database'], 'signups', $entry);
		
		# Add attributes
		$entry['userName'] = $userName;
		$entry['startTime'] = $startTime;
		
		# Insert the row
		if (!$result = $this->databaseConnection->insert ($this->settings['database'], 'signups', $entry)) {
			$error = 'There was a problem registering the signup.';
			return false;
		}
		
		# Return success
		return true;
	}
	
	
	# Model function to delete a signup
	private function deleteSignup ($supervisionId, $startTime, $userId /* assumed not trusted */, &$error = false)
	{
		# Assemble the data identity
		$entry = array (
			'supervisionId' => $supervisionId,
			'startTime' => $startTime,
			'userId' => $userId,
		);
		
		# Ensure the entry exists
		if (!$signup = $this->databaseConnection->selectOne ($this->settings['database'], 'signups', $entry)) {
			$error = 'There is no such signup.';
			return false;
		}
		
		# Ensure the user has rights
		if (!$this->userIsAdministrator) {
			if ($signup['userId'] != $this->user) {
				$error = 'You do not appear to have rights to delete this signup.';
				return false;
			}
		}
		
		# Delete the entry
		if (!$this->databaseConnection->delete ($this->settings['database'], 'signups', $entry)) {
			$error = 'There was a problem deleting the signup.';
			return false;
		}
		
		# Return success
		return true;
	}
	
	
	# Model function to get the courses
	private function getCourses ()
	{
		# Get the courses
		$data = $this->databaseConnection->select ($this->settings['database'], 'courses', array ('available' => '1'), array (), true, $orderBy = 'yearGroup, ordering, LENGTH(courseNumber), courseNumber, courseName');
		
		# Regroup as nested set
		$courses = array ();
		foreach ($data as $id => $course) {
			$yearGroup = $course['yearGroup'] . ':';
			$id = $course['id'];
			$courses[$yearGroup][$id] = ($course['courseNumber'] ? $course['courseNumber'] . ': ' : '') . $course['courseName'];
		}
		
		# Return the courses
		return $courses;
	}
	
	
	# Model function to perform supervision deletion
	private function doSupervisionDeletion ($id, &$error = false)
	{
		# Delete the supervision entry itself
		if (!$this->databaseConnection->delete ($this->settings['database'], $this->settings['table'], array ('id' => $id), $limit = 1)) {
			$error = 'There was a problem deleting the supervision.';
			return false;
		}
		
		# Delete the timeslots
		if (!$this->databaseConnection->delete ($this->settings['database'], 'timeslots', array ('supervisionId' => $id))) {
			$error = 'There was a problem deleting the timeslots associated with the supervision.';
			return false;
		}
		
		# Delete the signups, if any
		#!# The database library API currently unable to distinguish syntax error vs nothing to delete
		if (!$this->databaseConnection->delete ($this->settings['database'], 'signups', array ('supervisionId' => $id))) {
			$status = $this->databaseConnection->error ();
			if ($status[0] != '00000') {	// 00000 indicates OK
				$error = 'There was a problem deleting the signups associated with the timeslots of the supervision.';
				return false;
			}
		}
		
		# Return success
		return true;
	}
	
	
	# Function to implement the export of bookings as iCal
	private function iCal ($supervision, $startTime)
	{
		# Add the entry
		$entry = array (
			'title' => "Supervision with {$supervision['supervisorName']}",
			'startTime' => strtotime ($startTime),
			'untilTime' => strtotime ($startTime) + ($supervision['length'] * 60),
			'location' => $supervision['location'],
			'description' => "Supervision with {$supervision['supervisorName']} for course: {$supervision['courseName']}",
		);
		
		# Delegate to iCal class
		require_once ('ical.php');
		$ical = new ical ();
		$title = 'Supervision';
		$output = $ical->create (array ($entry), $title, 'ac.uk.cam.geog', 'Supervisions');
		
		# Serve the file, first flushing all previous HTML (including from auto_prepend_file)
		ob_clean ();
		flush ();
		echo $output;
		exit;
	}
}

?>
