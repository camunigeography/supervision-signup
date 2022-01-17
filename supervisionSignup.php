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
			'databaseStrictWhere' => true,
			'database' => 'supervisions',
			'table' => 'supervisions',
			'settingsTableExplodeTextarea' => array ('additionalSupervisors', 'yearGroups', 'lengths'),
			'tabUlClass' => 'tabsflat',
			'useCamUniLookup' => true,
			'emailDomain' => 'cam.ac.uk',
			'administrators' => true,
			'userIsStaffCallback' => 'userIsStaffCallback',		// Callback function
			'userYeargroupCallback' => 'userYeargroupCallback',	// Callback function
			'userNameCallback' => false,						// Callback function; useful if a better name source than Lookup (which tends only to have initials for forenames) is available
			'usersAutocomplete' => false,
			'authentication' => true,
			'lengthDefault' => 60,
			'yearGroups' => false,		// Set on settings page, e.g. Part IA, Part IB, Part II
			'organisationDescription' => 'the Department',
			'morningFirstHour' => 8,	// First hour that is in the morning; e.g. if set to 8, staff-entered time '8' would mean 8am rather than 8pm, and '7' would mean 7pm
			'enableSecondSupervisor' => true,
			'enableDescription' => true,
			'showSupervisorName' => true,
			'allowMultipleSignups' => false,
			'allowSameDayBookings' => false,
			'hidePastDays' => false,
			'privilegedUserDescription' => 'member of staff',
			'label' => 'supervision',
			'labelPlural' => 'supervisions',
			'containerLabel' => 'course',
			'containerLabelPlural' => 'courses',
			'homepageMessageHtml' => false,
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
				'tab' => "Sign up to a {$this->settings['label']}",
				'icon' => 'pencil',
			),
			'my' => array (
				'description' => "My {$this->settings['labelPlural']}",
				'url' => 'my/',
				'tab' => "My {$this->settings['labelPlural']}",
				'icon' => 'asterisk_orange',
				'enableIf' => $this->userIsStaff,
			),
			'aboutical' => array (
				'description' => "My {$this->settings['labelPlural']} - iCal",
				'url' => 'my/ical.html',
				'usetab' => 'my',
				'enableIf' => $this->userIsStaff,
			),
			'ical' => array (
				'description' => "My {$this->settings['labelPlural']} - iCal",
				'url' => 'my/supervisions.ics',
				'export' => true,
				'authentication' => false,
			),
			'add' => array (
				'description' => "Create a new signup sheet",
				'url' => 'add/',
				'tab' => 'Create a new signup sheet',
				'icon' => 'add',
				'enableIf' => ($this->userIsStaff || $this->userIsAdministrator),
			),
			'courses' => array (
				'description' => ucfirst ($this->settings['containerLabelPlural']),
				'url' => 'courses/',
				'tab' => ucfirst ($this->settings['containerLabelPlural']),
				'icon' => 'page_white_stack',
				'administrator' => true,
			),
			'importcourses' => array (
				'description' => 'Import course data',
				'url' => 'courses/import/',
				'usetab' => 'courses',
				'administrator' => true,
			),
			'statistics' => array (
				'description' => 'Usage statistics',
				'url' => 'statistics/',
				'parent' => 'admin',
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
			  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Username' PRIMARY KEY,
			  `active` enum('','Yes','No') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Yes' COMMENT 'Currently active?',
			  `privilege` enum('Administrator','Restricted administrator') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Administrator' COMMENT 'Administrator level'
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System administrators';
			
			-- Settings
			CREATE TABLE IF NOT EXISTS `settings` (
			  `id` int(11) NOT NULL COMMENT 'Automatic key (ignored)' PRIMARY KEY,
			  `homepageMessageHtml` TEXT NULL COMMENT 'Homepage message (if any)',
			  `supervisorsMessage` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Message (if any) to supervisors to appear on the supervision creation screen',
			  `additionalSupervisors` text COLLATE utf8mb4_unicode_ci COMMENT 'Additional supervisors (usernames, one per line)',
			  `academicYearStartsMonth` INT(2) NOT NULL DEFAULT '8' COMMENT '\'Current\' year starts on month',
			  `yearGroups` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Year groups (one per line)',
			  `timeslotsWeeksAhead` INT NOT NULL DEFAULT '14' COMMENT 'Number of weeks ahead to show in slot-setting interface',
			  `lengths` TEXT NOT NULL COMMENT 'Time lengths available, in minutes (one per line)',
			  `hideFinished` TINYINT NULL DEFAULT NULL COMMENT 'Hide finished entries from main listing, for ordinary users?'
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Settings';
			INSERT INTO settings (id, yearGroups, lengths) VALUES (1, 'First year', '15\n30\n45\n60\n90\n120');
			
			-- Supervisions
			CREATE TABLE `supervisions` (
			  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Supervision ID #',
			  `supervisor` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Supervisor username',
			  `supervisorName` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Supervisor name',
			  `supervisor2` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Second supervisor (if applicable) - username',
			  `supervisor2Name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Second supervisor (if applicable) - name',
			  `courseId` int(11) NOT NULL COMMENT 'Course',
			  `courseName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Course name',
			  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Supervision title',
			  `descriptionHtml` TEXT CHARACTER SET utf8 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Description',
			  `studentsPerTimeslot` ENUM('1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30') CHARACTER SET utf8 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '3' COMMENT 'Maximum students per timeslot',
			  `location` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Location(s)',
			  `length` int(11) NOT NULL COMMENT 'Length of time',
			  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Automatic timestamp',
			  `updatedAt` datetime NOT NULL COMMENT 'Updated at',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table of supervisions';
			
			-- Timeslots
			CREATE TABLE `timeslots` (
			  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `supervisionId` INT(11) NOT NULL COMMENT 'Supervision ID',
			  `startTime` datetime NOT NULL COMMENT 'Start datetime',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table of timeslots';
			
			-- Signups
			CREATE TABLE `signups` (
			  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `supervisionId` int(11) NOT NULL COMMENT 'Supervision ID',
			  `userId` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'User ID',
			  `userName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'User name',
			  `startTime` datetime NOT NULL COMMENT 'Start datetime',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table of timeslots';
			
			-- Courses
			CREATE TABLE `courses` (
			  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Automatic key',
			  `yearGroup` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Year group',
			  `courseNumber` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Course number',
			  `courseName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Course name',
			  `academicYear` VARCHAR(7) NOT NULL COMMENT 'Academic year',
			  `ordering` INT(1) NULL DEFAULT '5' COMMENT 'Ordering (1=first, 9=last)' AFTER `available`,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Courses';
			
			-- Users
			CREATE TABLE `users` (
			  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'User ID',
			  `token` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Token',
			  PRIMARY KEY (`id`),
			  UNIQUE KEY (`token`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table of users';
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
		
		# Parse out the lengths to add labels, e.g. array (30 => '30 minutes', 60 => '1 hour', 120 => '2 hours')
		$this->settings['lengths'] = $this->parseLengths ($this->settings['lengths']);
		
		# Current academic year for today's date
		$this->currentAcademicYear = timedate::academicYear ($this->settings['academicYearStartsMonth'], true, true);
		
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
			if (!in_array ($this->action, array ('feedback', 'ical'))) {		// Unless on feedback page or iCal output
				$html  = "\n<p>This system is only available to current students and staff of " . htmlspecialchars ($this->settings['organisationDescription']) . '.</p>';
				$html .= "\n<p>If you think you should have access, please <a href=\"{$this->baseUrl}/feedback.html\">contact us</a>.</p>";
				echo $html;
				return false;
			}
		}
		
	}
	
	
	# Function to parse out the lengths to add labels, e.g. array (30 => '30 minutes', 60 => '1 hour', 120 => '2 hours')
	private function parseLengths ($lengthValues)
	{
		# Convert each
		$lengths = array ();
		foreach ($lengthValues as $length) {
			$label = ($length < 60 ? "{$length} minutes" : round (($length / 60), 1) . ($length == 60 ? ' hour' : ' hours'));
			$lengths[$length] = $label;
		}
		
		# Return the list
		return $lengths;
	}
	
	
	# Welcome screen
	public function home ()
	{
		# Start the HTML
		$html = '';
		
		# Get the supervisions
		$supervisions = $this->getSupervisions ($this->userYeargroup);
		
		# Special message if present
		if ($this->settings['homepageMessageHtml']) {
			$html .= "\n" . '<div class="graybox">';
			if ($this->userIsAdministrator) {
				$html .= "\n<p class=\"actions right\"><a href=\"{$this->baseUrl}/settings.html\"><img src=\"/images/icons/pencil.png\" alt=\"\"> Edit introduction</a></p>";
			}
			$html .= $this->settings['homepageMessageHtml'];
			$html .= "\n</div>";
		}
		
		# List of supervisions
		$html .= "\n<h2>Sign up to a {$this->settings['label']}</h2>";
		if ($supervisions) {
			$html .= "\n<p>You can sign up to the following {$this->settings['labelPlural']} online:</p>";
			$html .= $this->supervisionsList ($supervisions, $this->settings['showSupervisorName']);
		} else {
			$html .= "\n<p>There are no {$this->settings['labelPlural']} available to sign up to yet, for the current academic year.</p>";
		}
		
		# Give links for staff
		if ($this->userIsStaff) {
			$html .= "\n<br />";
			$html .= "\n<h2>Create {$this->settings['label']} signup sheet</h2>";
			$html .= "\n<p>As a {$this->settings['privilegedUserDescription']}, you can <a href=\"{$this->baseUrl}/add/\" class=\"actions\"><img src=\"/images/icons/add.png\" alt=\"Add\" border=\"0\" /> Create a {$this->settings['label']} signup sheet</a>.</p>";
			$html .= "\n<br />";
			$html .= "\n<h2>My {$this->settings['labelPlural']}</h2>";
			$html .= "\n<p>As a {$this->settings['privilegedUserDescription']}, you can <a href=\"{$this->baseUrl}/my/\" class=\"actions\"><img src=\"/images/icons/asterisk_orange.png\" alt=\"Add\" border=\"0\" /> View {$this->settings['labelPlural']} you have set up</a>.</p>";
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
					if ($this->settings['hideFinished'] && !$this->userIsAdministrator && $supervision['hasFinished']) {continue;}	// If enabled, skip finished
					$list[$id]  = "<a href=\"{$supervision['href']}\"" . ($supervision['hasFinished'] ? ' class="finished"' : '') . '>';
					$list[$id] .= htmlspecialchars ($supervision['title']);
					if ($showSupervisor) {
						$list[$id] .= ' (';
						$list[$id] .= htmlspecialchars ($supervision['supervisorName']);
						if ($supervision['supervisor2'] && $supervision['supervisor2Name']) {
							$list[$id] .= ' / ' . htmlspecialchars ($supervision['supervisor2Name']);
						}
						$list[$id] .= ')';
					}
					$list[$id] .= '</a>';
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
	
	
	# Function to create a new signup sheet
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
			$html = "\n" . "<p><a href=\"{$this->baseUrl}/my/ical.html\">" . '<img src="/images/icons/extras/ical.gif" alt="iCal" title="iCal output - subscribe for your calendar" class="right" /></a></p>';
			$html .= "\n<p>You are running the {$this->settings['labelPlural']} listed below.</p>";
			$html .= "\n<p>You can view the student signups, or edit/delete a signup sheet, on each page.</p>";
			$html .= $this->supervisionsList ($supervisionsSupervising, false);
		} else {
			$html .= "\n<p>There are none.</p>";
			$html .= "\n<p>You can <a href=\"{$this->baseUrl}/add/\">create a new {$this->settings['label']} signup sheet</a>.</p>";
		}
		
		# Return the HTML
		echo $html;
	}
	
	
	# Function to create a user's token if not already present
	private function getToken ()
	{
		# Return it if already present
		if ($token = $this->databaseConnection->selectOneField ($this->settings['database'], 'users', 'token', array ('id' => $this->user))) {
			return $token;
		}
		
		# Generate a token; it is assumed that collisions are unlikely given a length of 16
		$token = application::generatePassword (16);
		
		# Add the entry
		$this->databaseConnection->insert ($this->settings['database'], 'users', array ('id' => $this->user, 'token' => $token));
		
		# Return the token
		return $token;
	}
	
	
	# Function to get the user from their token
	private function getUserFromToken ($token)
	{
		# Return the result, if any
		return $this->databaseConnection->selectOneField ($this->settings['database'], 'users', 'id', array ('token' => $_GET['token']));
	}
	
	
	# iCal instructions page for My supervisions
	public function aboutical ()
	{
		# Start the HTML
		$html = '';
		
		# Get the user's token if not already present
		$token = $this->getToken ();
		$icsUrl = "{$this->baseUrl}/my/supervisions.ics?token={$token}";
		
		# Delegate to iCal class
		require_once ('ical.php');
		$ical = new ical ();
		$html = $ical->instructionsLink ($icsUrl);
		
		# Return the HTML
		echo $html;
	}
	
	
	# iCal output for My supervisions
	public function ical ()
	{
		# Ensure that a token has been supplied, or end
		if (!isSet ($_GET['token']) || !strlen ($_GET['token'])) {
			application::sendHeader (401);
			echo "\n<p>ERROR: No token was supplied.</p>";
			return false;
		}
		
		# Look up the user from the token, or end
		if (!$userId = $this->getUserFromToken ($_GET['token'])) {
			sleep (1);
			application::sendHeader (403);
			echo "\n<p>ERROR: Invalid token.</p>";
			return false;
		}
		
		# Get the supervisions
		$supervisionsSupervising = $this->getSupervisions (false, $userId, true);
		
		# Serve iCal feed if required
		$this->iCalBookings ($supervisionsSupervising);
	}
	
	
	# Courses editing section, substantially delegated to the sinenomine editing component
	public function courses ($attributes = array (), $deny = false)
	{
		# Start the HTML
		$html = '';
		
		# Add link to import, if not using the record editor
		if (!isSet ($_GET['record'])) {
			$html .= "\n" . '<div class="graybox courses">';
			$html .= "\n<h3>Import {$this->settings['containerLabelPlural']} for new academic year</h3>";
			$html .= "\n<ul class=\"actions left\">\n<li><a href=\"{$this->baseUrl}/courses/import/\"><img src=\"/images/icons/add.png\" alt=\"Add\" border=\"0\" /> Import new {$this->settings['containerLabelPlural']}</a>\n</li>\n</ul>";
			$html .= "\n" . '</div>';
		}
		
		# Get the databinding attributes
		$dataBindingAttributes = array (
			'yearGroup' => array ('type' => 'select', 'values' => $this->settings['yearGroups'], 'description' => "You can add new year groups on the <a href=\"{$this->baseUrl}/settings.html#form_yearGroups\">settings page</a>.", ),		// NB: Strings must match response from userYeargroupCallback
			'ordering' => array ('type' => 'select', 'values' => range (1, 9), ),
			'academicYear' => array ('title' => 'Academic year, e.g. ' . (date ('Y') - 1) . '-' . date ('y'), ),
		);
		
		# Define general sinenomine settings
		$sinenomineExtraSettings = array (
			'submitButtonPosition' => 'bottom',
			'int1ToCheckbox' => true,
			'fieldFiltering' => false,
		);
		
		# Delegate to the standard function for editing
		$html .= "\n" . '<div class="graybox courses">';
		$html .= "\n<h3>Edit {$this->settings['containerLabel']} data</h3>";
		$html .= "\n<p>Here you can correct existing {$this->settings['containerLabel']} entries. However, you should not delete old entries, as they will be attached to existing {$this->settings['labelPlural']}.</p>";
		$html .= $this->editingTable ('courses', $dataBindingAttributes, 'ultimateform', false, $sinenomineExtraSettings);
		$html .= "\n" . '</div>';
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to provide mass import of course data
	public function importcourses ()
	{
		# Start the HTML
		$html = "\n<p>Here you can import {$this->settings['containerLabel']} data.</p>";
		
		# Define the required headers
		$expectedHeaders = $this->databaseConnection->getFieldnames ($this->settings['database'], 'courses', false, false, $excludeAuto = true);
		
		# Create a form
		$form = new form (array (
			'formCompleteText' => false,
			'display' => 'paragraphs',
		));
		$form->heading ('p', "To add the data, prepare a spreadsheet like this example, which you should then paste in below. It must contain the following headers in the first row (as per <a href=\"{$this->baseUrl}/courses/\">existing examples</a>):<br />" . '<tt><strong>' . implode ('</tt></strong>, <strong><tt>', $expectedHeaders) . '</strong></tt>');
		$form->heading ('p', "<strong>Example:</strong><br /><img src=\"{$this->baseUrl}/images/import.png\" alt=\"Import example\" border=\"0\" width=\"600\" />");
		$form->textarea (array (
			'name'			=> 'data',
			'title'			=> 'Paste in your spreadsheet contents, including the headers',
			'required'		=> true,
			'rows'			=> 15,
			'cols'			=> 90,
			'placeholder'	=> 'See the example above',
		));
		
		# Do checks on the pasted data
		require_once ('csv.php');
		if ($unfinalisedData = $form->getUnfinalisedData ()) {
			if ($unfinalisedData['data']) {
				
				# Arrange the data
				$data = csv::tsvToArray ($unfinalisedData['data']);
				
				# Ensure there is some data
				if (count ($data) < 2) {
					$form->registerProblem ('data', 'There must be at least one line of data in the pasted spreadsheet block.');
				}
				
				# Ensure the headings are all present
				if (isSet ($expectedHeaders)) {
					$headersPresent = array_keys ($data[0]);
					if ($headersPresent !== $expectedHeaders) {
						$form->registerProblem ('headers', 'The headers in the pasted spreadsheet block must be exactly <strong><tt>' . implode ('</tt>, <tt>', $expectedHeaders) . '</tt></strong>');
					}
				}
			}
		}
		
		# Process the form or end
		if (!$result = $form->process ($html)) {
			echo $html;
			return false;
		}
		
		# Convert the data into a CSV structure
		$data = csv::tsvToArray ($result['data']);
		
		# Insert the data
		if (!$result = $this->databaseConnection->insertMany ($this->settings['database'], 'courses', $data)) {
			echo "\n<p class=\"warning\">Error:</p>";
			application::dumpData ($this->databaseConnection->error ());
			return false;
		}
		
		# Confirm success<br>
		echo "\n<div class=\"graybox\">";
		echo "\n\t<p class=\"success\">{$this->tick} The courses have been successfully imported. They can now be viewed or edited on the <a href=\"{$this->baseUrl}/courses/\">courses list page</a>.</p>";
		echo "\n</div>";
		
		# Return the HTML
		echo $html;
	}
	
	
	# Supervision editing form
	private function supervisionForm ($supervision = array (), $editMode = false)
	{
		# Start the HTML
		$html = '';
		
		# Get the courses
		if (!$courses = $this->getCourses ()) {
			$html  = "\n<p>The <a href=\"{$this->baseUrl}/courses/\">list of {$this->settings['containerLabelPlural']}</a> available for the current year has not yet been loaded, so it is not yet possible to create a new signup sheet.</p>";
			echo $html;
			return;
		}
		
		# Create the timeslots, using the Mondays from the start of the week for the current date (for a new supervision) or the creation date (editing an existing one)
		$allDays = $this->calculateTimeslotDates ($supervision, $editMode);
		
		# Compile the timeslots template HTML, and obtain the timeslot fields created
		$fieldnamePrefix = 'timeslots_';
		$dateTextFormat = 'D jS M';
		$timeslotsHtml = $this->timeslotsHtml ($allDays, $fieldnamePrefix, $dateTextFormat, $timeslotsFields /* returned by reference */);
		
		# If editing, parse existing timeslots to the textarea format
		$timeslotsDefaults = $this->parseExistingTimeslots (($editMode ? $supervision : array ()));
		
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
		
		# Show message to supervisors if set
		if ($this->settings['supervisorsMessage']) {
			$form->heading ('', "\n<p class=\"warning\">" . htmlspecialchars ($this->settings['supervisorsMessage']) . '</p>');
		}
		
		$exclude = array ('id', 'supervisor', 'supervisorName', 'supervisor2Name', 'courseName');	// Fixed data fields, handled below
		if (!$this->settings['enableSecondSupervisor']) {
			$exclude[] = 'supervisor2';
		}
		if (!$this->settings['enableDescription']) {
			$exclude[] = 'descriptionHtml';
		}
		$form->dataBinding (array (
			'database' => $this->settings['database'],
			'table' => $this->settings['table'],
			'data' => $supervision,
			'intelligence' => true,
			'exclude' => $exclude,
			'attributes' => array (
				'courseId' => array ('type' => 'select', 'values' => $courses, ),
				'title' => array ('regexp' => '[a-z]+', ),	// Prevent ALL UPPER CASE text
				'supervisor2'  => ($this->settings['usersAutocomplete'] ? array ('autocomplete' => $this->settings['usersAutocomplete'], 'autocompleteOptions' => array ('delay' => 0), ) : array ()),
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
		$proposedStartTimesPerDate = array ();
		if ($unfinalisedData = $form->getUnfinalisedData ()) {
			
			# Obtain the already chosen signups, by date
			$alreadyChosenSignupsByDate = ($editMode ? $this->nestTimeslotsByDate ($supervision['signupsByTimeslot']) : array ());
			
			# Check start times via parser, by checking each field
			$timeslots = application::arrayFields ($unfinalisedData, $timeslotsFields);
			foreach ($timeslots as $fieldname => $times) {
				
				# Skip if no data submitted
				if (!$times) {continue;}
				
				# Remove the fieldname prefix to create the date
				$date = str_replace ($fieldnamePrefix, '', $fieldname);	// e.g. 2016-11-08
				
				# Parse out the text block to start times
				if (!$proposedStartTimesPerDate[$date] = $this->parseStartTimes ($times, $date, $dateTextFormat, $errorHtml /* returned by reference */)) {
					$form->registerProblem ('starttimeparsefailure', $errorHtml, $fieldname);
				}
				
				# Add constraint that number of slots cannot be reduced if that number is filled
				if ($unfinalisedData['studentsPerTimeslot']) {
					if ($editMode) {
						$greatestTotalSignups = 0;
						foreach ($alreadyChosenSignupsByDate as $date => $alreadyChosenSignupsThisDateByTimeslot) {
							foreach ($alreadyChosenSignupsThisDateByTimeslot as $datetime => $signupsThisTimeslot) {
								$totalSignups = count ($signupsThisTimeslot);
								$greatestTotalSignups = max ($greatestTotalSignups, $totalSignups);
							}
						}
						if ($unfinalisedData['studentsPerTimeslot'] < $greatestTotalSignups) {
							$form->registerProblem ('timeslotloss', "You cannot reduce to {$unfinalisedData['studentsPerTimeslot']} students per timeslot (max) because there is already a timeslot with {$greatestTotalSignups} existing student signups.");
						}
					}
				}
			}
			
			# Ensure that at least one timeslot has been created
			if (!$proposedStartTimesPerDate) {
				$form->registerProblem ('notimeslots', 'No timeslots have been set.');
			}
			
			# Prevent deletion of slots that have already been chosen by a student
			if ($missingAlreadyChosen = $this->missingAlreadyChosen ($alreadyChosenSignupsByDate, $proposedStartTimesPerDate, $dateTextFormat)) {
				$form->registerProblem ('missingchosen', 'Some of the timeslots that you attempted to remove already have signups: <em>' . implode ('</em>, <em>', $missingAlreadyChosen) . '</em>, so you need to reinstate those times in the timeslots list below.');
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
			
			# Add in the second supervisor name, if applicable
			if ($result['supervisor2']) {
				$result['supervisor2Name'] = '?';
				if ($this->settings['userNameCallback']) {
					$userNameCallback = $this->settings['userNameCallback'];
					if ($supervisor2Name = $userNameCallback ($result['supervisor2'])) {
						$result['supervisor2Name'] = $supervisor2Name;
					}
				}
			}
			
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
			foreach ($proposedStartTimesPerDate as $date => $startTimes) {
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
				$subject = ucfirst ($this->settings['label']) . " signup sheet created: {$result['title']} ({$result['courseName']})";
				$message = "\nThis e-mail confirms that you have created an online {$this->settings['label']} signup sheet.";
				$message .= "\n\nYou can view student signups, or edit the details, at:\n\n{$_SERVER['_SITE_URL']}{$redirectTo}";
				$message .= "\n\nDon't forget that you need to e-mail the relevant students to tell them about the {$this->settings['label']} signup sheet and give them this link.";
				$message .= "\n\nPlease note that you will not receive any further e-mails about this {$this->settings['label']} signup sheet.";
				$extraHeaders  = 'From: Webserver <' . $this->settings['webmaster'] . '>';
				// $extraHeaders .= "\r\n" . 'Bcc: ' . $this->settings['administratorEmail'];
				application::utf8Mail ($to, $subject, wordwrap ($message), $extraHeaders);
			}
			
			# Redirect the user
			$html .= application::sendHeader (302, $redirectTo, true);
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to nest timeslots by date
	private function nestTimeslotsByDate ($timeslots)
	{
		# Nest by date
		$timeslotsByDate = array ();
		foreach ($timeslots as $startTime => $signups) {
			list ($date, $time) = explode (' ', $startTime, 2);
			$timeslotsByDate[$date][$startTime] = $signups;
		}
		
		# Return the list
		return $timeslotsByDate;
	}
	
	
	# Function to determine the timeslots based on a start time
	private function calculateTimeslotDates ($supervision, $editMode)
	{
		# Start from either today or, if there is a supervision being edited, the creation date
		# NB This checks for edit mode explicitly, rather than for $supervision having data, as cloning will have data but should have a fresh set of dates
		$timestamp = ($editMode ? strtotime ($supervision['createdAt']) : false);
		
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
		$html  = "\n\t\t\t\t\t\t" . '<table class="timeslots border">';
		
		# Add the header row
		$html .= "\n\t\t\t\t\t\t\t" . '<tr>';
		$html .= "\n\t\t\t\t\t\t\t\t" . '<td></td>';
		$days = array ('Monday', 'Tuesday', 'Wednesday','Thursday','Friday', 'Saturday', 'Sunday');
		foreach ($days as $index => $day) {
			$html .= "\n\t\t\t\t\t\t\t\t" . '<th class="' . strtolower ($day) . ($index <= 4 ? ' weekday' : '') . '">' . $day . '</th>';
		}
		$html .= "\n\t\t\t\t\t\t\t" . '</tr>';
		
		# Create a list of the fields, to be passed back by reference
		$timeslotsFields = array ();
		
		# Start each week
		foreach ($allDays as $weekStartUnixtime => $daysOfWeek) {
			$html .= "\n\t\t\t\t\t\t\t" . '<tr>';
			$html .= "\n\t\t\t\t\t\t\t\t" . '<td class="comment">Start times, e.g. :<br /><br /><span class="small">11<br />12<br />1.30</span></td>';
			
			# Add each day, saving the fieldname
			$index = 0;
			foreach ($daysOfWeek as $dayUnixtime => $dayYmd) {
				$html .= "\n\t\t\t\t\t\t\t\t" . '<td class="' . strtolower (date ('l', $dayUnixtime)) . ($index <= 4 ? ' weekday' : '') . '">';
				$html .= date ($dateTextFormat, $dayUnixtime) . ':<br />';
				$timeslotsFields[$dayUnixtime] = $fieldnamePrefix . $dayYmd;
				$html .= '{' . $timeslotsFields[$dayUnixtime] . '}';
				$html .= '</td>';
				$index++;
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
		foreach ($supervision['signupsByTimeslot'] as $timeslot => $signups) {
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
			
			<script type=\"text/javascript\">
				$(document).ready(function() {
					$('#autocopy').click (function (e) {
						if (confirm ('Are you sure? This will replace any existing text in each Monday-Friday box.')) {
							var value = $('table.timeslots textarea:first').val ();
							$('table.timeslots td.weekday textarea').val (value);
							e.preventDefault ();
						}
					});
				});
			</script>
			
			{[[PROBLEMS]]}
			
			<table class=\"lines setdetails\">
				
				" . ($this->settings['supervisorsMessage'] ? "<tr>
					<td colspan=\"2\">{_heading1}</td>
				</tr>
				" : '') . "
				
				<tr>
					<td colspan=\"2\"><h3>Main details</h3></td>
				</tr>
				<tr>
					<td>" . ucfirst ($this->settings['containerLabel']) . ": *</td>
					<td>{courseId}</td>
				</tr>
				<tr>
					<td>Title: *</td>
					<td>{title}</td>
				</tr>
				" . ($this->settings['enableDescription'] ? "<tr>
					<td>Description (optional):<br /><br />(NB: Web addresses will automatically become links.)</td>
					<td>{descriptionHtml}</td>
				</tr>
				" : '') . "
				" . ($this->settings['enableSecondSupervisor'] ? "<tr>
					<td>2nd supervisor (if applicable) - username</td>
					<td>{supervisor2}</td>
				</tr>
				" : '') . "
				<tr>
					<td colspan=\"2\"><h3>Booking settings</h3></td>
				</tr>
				<tr>
					<td>Maximum students per timeslot: *</td>
					<td>{studentsPerTimeslot}</td>
				</tr>
				<tr>
					<td>Location details: *</td>
					<td>{location}</td>
				</tr>
				<tr>
					<td>Length of time: *</td>
					<td>{length}</td>
				</tr>
				
				<tr>
					<td colspan=\"2\">
						<h3>Timeslots</h3>
						<p><a href=\"#\" id=\"autocopy\">Copy first box to each weekday&hellip;</a></p>
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
	private function missingAlreadyChosen ($alreadyChosenSignupsByDate, $proposedStartTimesPerDate, $dateTextFormat)
	{
		# Start a list of missing already-chosen signups
		$missingAlreadyChosen = array ();
		
		# Loop through the existing signups as the comparator to ensure that each such slot remains proposed, rather than the other way round, as the whole date may have been deleted
		foreach ($alreadyChosenSignupsByDate as $date => $alreadyChosenSignupsThisDateByTimeslot) {
			foreach ($alreadyChosenSignupsThisDateByTimeslot as $datetime => $signupsThisTimeslot) {
				if ($signupsThisTimeslot) {
					
					# If the date is missing, or the time within that date, is missing, register it
					if (!isSet ($proposedStartTimesPerDate[$date]) || !in_array ($datetime, $proposedStartTimesPerDate[$date])) {
						$dateString = date ($dateTextFormat, strtotime ($datetime));
						$time = timedate::simplifyTime (date ('H:i:s', strtotime ($datetime)));
						$missingAlreadyChosen[$dateString][] = $time;
					}
				}
			}
		}
		
		# Compile to a string, in the format date (time1, time2, ...)
		foreach ($missingAlreadyChosen as $dateString => $times) {
			$missingAlreadyChosen[$dateString] = $dateString . ' (' . implode (', ', $times) . ')';
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
		$userHasEditRights = ($this->userIsAdministrator || ($supervision['supervisor'] == $this->user) || ($supervision['supervisor2'] && ($supervision['supervisor2'] == $this->user)));
		
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
		$html .= "\n<h2>Sign up to a {$this->settings['label']}</h2>";
		
		# Show the supervision
		$html .= $this->showSupervision ($supervision);
		
		# Show list of signups for easy export
		if ($userHasEditRights) {
			$html .= $this->listSignedupUsers ($supervision['signupsByTimeslot']);
		}
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to show users that have signed up, to enable easier e-mailing
	private function listSignedupUsers ($signupsByTimeslot)
	{
		# Extract the usernames, converting to e-mail addresses
		$emails = array ();
		$usernames = array ();
		foreach ($signupsByTimeslot as $timeslot => $signups) {
			foreach ($signups as $signup) {
				$emails[] = $signup['userId'] . '@' . $this->settings['emailDomain'];
				$usernames[] = $signup['userId'];
			}
		}
		
		# Unique and sort
		$emails = array_unique ($emails);
		sort ($emails);
		$usernames = array_unique ($usernames);
		sort ($usernames);
		
		# Compile the HTML
		$html  = "\n<h3>Students signed up</h3>";
		$html .= "\n<p>This list, available only to you as the supervisor, shows the list of those currently signed up, as e-mails and usernames, as per the list above:</p>";
		$html .= "\n<div class=\"graybox\">";
		if ($emails) {
			$html .= "\n<p><em>As e-mails (for e-mailing):</em></p>";
			$html .= "\n<p>" . implode (', ', $emails) . '</p>';
			$html .= "\n<p><em>As e-mails (for e-mailing with Outlook):</em></p>";
			$html .= "\n<p>" . implode ('; ', $emails) . '</p>';
			$html .= "\n<p><em>As usernames (for CamCORS):</em></p>";
			$html .= "\n<p>" . implode ('<br />', $usernames) . '</p>';
		} else {
			$html .= "\n<p><em>(No signups yet.)</em></p>";
		}
		$html .= "\n</div>";
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to show a supervision
	private function showSupervision ($supervision)
	{
		# Start the HTML
		$html = '';
		
		# Determine if the user has signed-up already
		$userSignup = false;
		foreach ($supervision['signupsByTimeslot'] as $timeslot => $signups) {
			foreach ($signups as $id => $signup) {
				if ($signup['userId'] == $this->user) {
					$userSignup = $signup;
					break;
				}
			}
		}
		
		# Add iCal export button
		$icalHtml = '';
		if ($userSignup) {
			$icalHtml = "\n" . "<p><a href=\"{$this->baseUrl}/{$supervision['id']}/supervision{$supervision['id']}.ics\">" . '<img src="/images/icons/extras/ical.gif" alt="iCal" title="iCal output - follow this link for information on how to export to your calendar" class="right" /></a></p>';
		}
		
		# Serve iCal feed if required
		if ($userSignup) {
			if (isSet ($_GET['ical'])) {
				$this->iCalBooking ($supervision, $userSignup['startTime']);
			}
		}
		
		# Create the supervision page
		$html .= "\n<h3>" . htmlspecialchars ($supervision['title']) . '</h3>';
		$html .= $icalHtml;
		$html .= "\n<p>";
		$html .= "\n\tYear group: <strong>" . htmlspecialchars ($supervision['yearGroup']) . '</strong><br />';
		$html .= "\n\t" . ucfirst ($this->settings['containerLabel']) . ': <strong>' . htmlspecialchars ($supervision['courseName']) . '</strong>';
		$html .= "\n</p>";
		if ($this->settings['showSupervisorName']) {
			$html .= "\n<p>With: ";
			$html .= '<strong>' . htmlspecialchars ($supervision['supervisorName']) . ' &lt;' . $supervision['supervisor'] . '&gt;' . '</strong>';
			if ($supervision['supervisor2'] && $supervision['supervisor2Name']) {
				$html .= ' / <strong>' . htmlspecialchars ($supervision['supervisor2Name']) . ' &lt;' . $supervision['supervisor2'] . '&gt;' . '</strong>';
			}
			$html .= '</p>';
		}
		if ($supervision['descriptionHtml']) {
			//$html .= "\n<h4>Description:</h4>";
			$html .= "\n<div class=\"graybox\">";
			$html .= "\n" . application::makeClickableLinks ($supervision['descriptionHtml']);
			$html .= "\n</div>";
		}
		$html .= "\n<h4>Location:</h4>";
		$html .= "\n<div class=\"graybox\">";
		$html .= "\n<p>" . nl2br (application::makeClickableLinks ($supervision['location'])) . '</p>';
		$html .= "\n</div>";
		$html .= "\n<br />";
		$html .= "\n<h3 id=\"timeslots\">Time slots</h3>";
		
		# Add the timeslot if required, determining the posted slot
		if (isSet ($_POST['timeslot']) && is_array ($_POST['timeslot']) && count ($_POST['timeslot']) == 1) {
			$startTime = key ($_POST['timeslot']);
			if (array_key_exists ($startTime, $supervision['signupsByTimeslot'])) {
				if (!$this->addSignup ($supervision['id'], $startTime, $this->user, $this->userName, $error /* returned by reference */)) {
					$html .= "\n<p class=\"warning\">{$error}</p>";
					return $html;
				}
				
				# Determine the URL to redirect to
				$refreshUrl = $_SERVER['_PAGE_URL'] . '#timeslot' . strtotime ($startTime);
				
				# Refresh the page
				$html .= application::sendHeader (302, $refreshUrl, $redirectMessage = true);
				return $html;
			}
		}
		
		# Delete the timeslot if required
		if (isSet ($_POST['delete']) && is_array ($_POST['delete']) && count ($_POST['delete']) == 1) {
			$submittedToken = key ($_POST['delete']);
			if (substr_count ($submittedToken, ',')) {
				list ($startTime, $userId) = explode (',', $submittedToken, 2);
				if (array_key_exists ($startTime, $supervision['signupsByTimeslot'])) {
					if (!$this->deleteSignup ($supervision['id'], $startTime, $userId, $error /* returned by reference */)) {
						$html .= "\n<p class=\"warning\">{$error}</p>";
						return $html;
					}
					
					# Determine the URL to redirect to
					$refreshUrl = $_SERVER['_PAGE_URL'] . '#timeslot' . strtotime ($startTime);
					
					# Refresh the page
					$html .= application::sendHeader (302, $refreshUrl, $redirectMessage = true);
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
			$abbreviations[$datePeriod] = preg_replace ("/^({$datePeriodAbbreviated})(.+)$/", '\1<span class="abbreviation">\2</span>', $datePeriod);
		}
		
		# Create the timeslot buttons
		$formTarget = $_SERVER['_PAGE_URL'] . '#timeslots';
		$html .= "\n\n<form class=\"timeslots\" name=\"timeslot\" action=\"" . htmlspecialchars ($formTarget) . "\" method=\"post\">";
		$html .= "\n\n\t<table class=\"lines\">";
		$userSlotPassed = false;
		foreach ($timeslotsByDate as $date => $timeslotsForDate) {
			
			# If required, hide days that have now passed
			if ($this->settings['hidePastDays']) {
				if ($date < $today) {
					continue;
				}
			}
			
			$totalThisDate = count ($timeslotsForDate);
			$first = true;
			foreach ($timeslotsForDate as $id => $timeFormatted) {
				if ($this->settings['allowSameDayBookings']) {
					$endTime = strtotime ($supervision['timeslots'][$id]) + ($supervision['length'] * 60);
					$editable = ($endTime > time ());	// I.e. hasn't yet ended
				} else {
					$editable = ($date > $today);
				}
				$indexValue = $supervision['timeslots'][$id];
				$unixTimestamp = strtotime ($supervision['timeslots'][$id]);
				$html .= "\n\t\t<tr" . ($editable ? '' : ' class="uneditable"') . ' id="timeslot' . $unixTimestamp . '">';
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
					$slotTaken = (isSet ($supervision['signupsByTimeslot'][$startTime][$i]));
					if ($slotTaken) {
						$signup = $supervision['signupsByTimeslot'][$startTime][$i];
						$removeHtml = '';
						if ($editable) {
							if ($signup['userId'] == $this->user || $this->userIsAdministrator) {
								$removeHtml = '<div class="delete"><input type="submit" name="delete[' . $indexValue . ',' . $signup['userId'] . ']" value="" onclick="return confirm(\'Are you sure?\');"></div>';	// See: http://stackoverflow.com/a/1193338/180733
							}
						}
						$html .= "<div class=\"timeslot " . ($signup['userId'] == $this->user ? 'me' : 'taken') . "\">{$removeHtml}<p>{$signup['userName']}<br /><span>{$signup['userId']}</span></p></div>";
						if ($signup['userId'] == $this->user) {
							$showButton = false;
							if (!$editable && !$this->settings['allowMultipleSignups']) {$userSlotPassed = true;}
						}
					} else {
						if ($showButton && !$userSlotPassed) {
							$label = ($userSignup && !$this->settings['allowMultipleSignups'] ? 'Change to here' : 'Sign up');
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
	
	
	# Function to clone a supervision; this clones the details but not the timeslots
	private function cloneSupervision ($supervision)
	{
		# Remove the ID and timestamps
		unset ($supervision['id']);
		unset ($supervision['createdAt']);
		unset ($supervision['updatedAt']);
		
		# Remove the timeslots
		unset ($supervision['timeslots']);
		unset ($supervision['signupsByTimeslot']);
		
		# Add note
		$html = "\n<p>On this page, you can copy the details of an existing entry. However, this will not copy timeslots, which you can enter freshly below.</p>";
		
		# Run the form with the details supplied
		$html .= $this->supervisionForm ($supervision);
		
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
		
		# Get the student signup data (which may be empty)
		$signups = $this->databaseConnection->select ($this->settings['database'], 'signups', array ('supervisionId' => $id), array ('id', 'userId', 'userName', 'startTime'), true, $orderBy = 'startTime, id');
		
		# Arrange signups by timeslot
		$supervision['signupsByTimeslot'] = $this->signupsByTimeslot ($supervision['timeslots'], $signups);
		
		// application::dumpData ($supervision);
		
		# Return the collection
		return $supervision;
	}
	
	
	# Helper function to arrange existing signups by timeslot
	private function signupsByTimeslot ($timeslots, $signups)
	{
		# Initialise to ensure all slots exist
		$signupsByTimeslot = array ();
		foreach ($timeslots as $startTime) {
			$signupsByTimeslot[$startTime] = array ();
		}
		
		# Filter, arranging by date
		foreach ($signups as $id => $signup) {
			$startTime = $signup['startTime'];
			$signupsByTimeslot[$startTime][] = $signup;	// Indexed from 0
		}
		
		# Return the list
		return $signupsByTimeslot;
	}
	
	
	# Model function to get supervisions, arranged hierarchically
	private function getSupervisions ($yeargroup = false, $supervisor = false, $includeTimeslots = false)
	{
		# Add constraints if required
		$preparedStatementValues = array ();
		$preparedStatementValues['academicYear'] = $this->currentAcademicYear;
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
				supervisor2,
				supervisor2Name,
				title,
				length,
				location,
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
			WHERE
				academicYear = :academicYear
				" . ($yeargroup ? ' AND yearGroup = :yearGroup' : '') . "
				" . ($supervisor ? ' AND supervisor = :supervisor' : '') . "
			GROUP BY supervisions.id
			ORDER BY courses.yearGroup, id
		;";
		$supervisions = $this->databaseConnection->getData ($query, "{$this->settings['database']}.{$this->settings['table']}", true, $preparedStatementValues);
		
		# Add link to each
		foreach ($supervisions as $id => $supervision) {
			$supervisions[$id]['href'] = $this->baseUrl . '/' . $id . '/';
		}
		
		# Obtain the timeslot data
		if ($includeTimeslots) {
			
			# Initialise timeslots and signups by timeslot
			foreach ($supervisions as $id => $supervision) {
				$supervisions[$id]['timeslots'] = array ();
				$supervisions[$id]['signupsByTimeslot'] = array ();
			}
			
			# Get the timeslots as a single query
			$timeslots = $this->databaseConnection->select ($this->settings['database'], 'timeslots', array ('supervisionId' => array_keys ($supervisions)), array ('id', 'supervisionId', 'startTime'), true, $orderBy = 'supervisionId, startTime');
			
			# Regroup by supervision
			foreach ($timeslots as $id => $timeslot) {
				$supervisionId = $timeslot['supervisionId'];
				$supervisions[$supervisionId]['timeslots'][$id] = $timeslot['startTime'];
			}
			
			# Get the student signup data (which may be empty)
			$signups = $this->databaseConnection->select ($this->settings['database'], 'signups', array ('supervisionId' => array_keys ($supervisions)), array ('id', 'supervisionId', 'userId', 'userName', 'startTime'), true, $orderBy = 'supervisionId, startTime, id');
			
			# Regroup by supervision
			$signupsBySupervision = application::regroup ($signups, 'supervisionId');
			
			# Add the timeslots
			foreach ($signupsBySupervision as $id => $signups) {
				$supervisions[$id]['signupsByTimeslot'] = $this->signupsByTimeslot ($supervisions[$id]['timeslots'], $signupsBySupervision[$id]);
			}
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
		
		# Clear any existing entry for this user, unless multiple signups are enabled
		if (!$this->settings['allowMultipleSignups']) {
			$this->databaseConnection->delete ($this->settings['database'], 'signups', $entry);
		}
		
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
		$data = $this->databaseConnection->select ($this->settings['database'], 'courses', array ('academicYear' => $this->currentAcademicYear), array (), true, $orderBy = 'yearGroup, ordering, LENGTH(courseNumber), courseNumber, courseName');
		
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
	
	
	# Function to implement the export of multiple bookings as iCal
	private function iCalBookings ($supervisions)
	{
		# Loop through each supervision
		$events = array ();
		foreach ($supervisions as $supervision) {
			
			# Loop through each timeslot and create an event for it
			foreach ($supervision['signupsByTimeslot'] as $startTime => $signupsByTimeslot) {
				
				# Assemble the list of signups for this timeslot
				$names = array ();
				foreach ($signupsByTimeslot as $signup) {
					$names[] = $signup['userName'] . ' (' . $signup['userId'] . ')';
				}
				
				# Compile the event
				$event = array (
					'title' => "Supervision: {$supervision['courseName']} - {$supervision['title']}",
					'startTime' => strtotime ($startTime),
					'untilTime' => strtotime ($startTime) + ($supervision['length'] * 60),
					'location' => $supervision['location'],
					'description' => "Supervision: {$supervision['courseName']}" . ($names ? ", with students: " . implode (', ', $names) . '.' : ". (No students signed up yet.)"),
				);
				
				# Register the event
				$events[] = $event;
			}
		}
		
		# Serve the iCal
		$this->serveICal ($events);
	}
	
	
	# Function to implement the export of a booking as iCal
	private function iCalBooking ($supervision, $startTime)
	{
		# Add the entry
		$event = array (
			'title' => "Supervision with {$supervision['supervisorName']}",
			'startTime' => strtotime ($startTime),
			'untilTime' => strtotime ($startTime) + ($supervision['length'] * 60),
			'location' => $supervision['location'],
			'description' => "Supervision with {$supervision['supervisorName']} for course: {$supervision['courseName']} - {$supervision['title']}",
		);
		
		# Serve the iCal
		$this->serveICal (array ($event));
	}
	
	
	# Function to serve an iCal for one or more entries
	private function serveICal ($events)
	{
		# Delegate to iCal class
		require_once ('ical.php');
		$ical = new ical ();
		$title = (count ($events) == 1 ? 'Supervision' : 'Supervisions');
		$output = $ical->create ($events, $title, 'ac.uk.cam.geog', 'Supervisions');
		
		# Serve the file, first flushing all previous HTML (including from auto_prepend_file)
		ob_clean ();
		flush ();
		echo $output;
		exit;
	}
	
	
	# Usage statistics page
	public function statistics ()
	{
		# Start the HTML
		$html = '';
		
		# Define the queries as a set of subqueries
		$query = "SELECT
			
			-- Supervisions created
			(SELECT COUNT(id) FROM supervisions) AS 'Supervisions created',
			
			-- Unique supervisors
			(SELECT COUNT(DISTINCT supervisor) FROM supervisions) AS 'By unique supervisors',
			
			-- Signups
			(SELECT COUNT(id) FROM signups) AS 'Signups',
			
			-- Student users
			(SELECT COUNT(DISTINCT userId) FROM signups) AS 'Unique student users'
		;";
		
		# Get the data
		$statistics = $this->databaseConnection->getOne ($query);
		
		# Apply number formatting
		foreach ($statistics as $key => $value) {
			$statistics[$key] = number_format ($value);
		}
		
		# Render as a table
		$html .= application::htmlTableKeyed ($statistics);
		
		# Show supervisors
		$query = "SELECT
			CONCAT(supervisorName, ' <', supervisor, '>', ' (', COUNT(*), ')') AS supervisor
			FROM supervisions
			GROUP BY supervisor
			ORDER BY SUBSTR(supervisorName, INSTR(supervisorName, ' '))		-- I.e. by name after first space in string
		;";
		$supervisors = $this->databaseConnection->getPairs ($query);
		
		# Show list
		$html .= application::htmlUl ($supervisors, 0, NULL, true, $sanitise = true);
		
		# Return the HTML
		echo $html;
	}
	
	
	# Settings
	public function settings ($dataBindingSettingsOverrides = array ())
	{
		# Define overrides
		$dataBindingSettingsOverrides = array (
			'attributes' => array (
				'homepageMessageHtml' => array ('editorToolbarSet' => 'BasicLongerFormat', 'config.width' => 400, 'config.height' => 150, ),
				'supervisorsMessage' => array ('cols' => 50, ),
				'additionalSupervisors' => array (
					'type' => 'select',
					'multiple' => true,
					'expandable' => true,
					'defaultPresplit' => true,
					'separator' => "\n",
					'autocomplete' => $this->settings['usersAutocomplete'],
					'autocompleteOptions' => array ('delay' => 0),
					'output' => array ('processing' => 'compiled'),
					'description' => 'Type a name or username to get a username;<br />One person per line only.',
				),
				'yearGroups' => array ('cols' => 50, 'description' => 'Do not delete/amend any entry currently in use.'),
				'lengths' => array ('cols' => 10, 'description' => 'Do not delete any entry currently in use.'),
			),
		);
		
		# Run the main settings system with the overriden attributes
		return parent::settings ($dataBindingSettingsOverrides);
	}
}

?>
