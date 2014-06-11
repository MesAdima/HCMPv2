<?php
class Log extends Doctrine_Record {
	public function setTableDefinition() 
	{
		$this -> hasColumn('id', 'int',50);	
		$this -> hasColumn('user_id', 'int',50);	
		$this -> hasColumn('action', 'varchar',50);
		$this -> hasColumn('action_id', 'int',50);
		$this -> hasColumn('start_time_of_event', 'date');
		$this -> hasColumn('end_time_of_event', 'date');
	
	}

	public function setUp() 
	{
		$this -> setTableName('log');
		
	}
	
	public static function get_active_login($option,$option_id)
	{
		switch ($option) {
			case 'county':
				$q = Doctrine_Manager::getInstance()->getCurrentConnection();
				$result = $q->execute("SELECT DISTINCT  `user_id` , f.facility_name
				FROM log l, user u, facilities f, districts d, counties c
				WHERE l.user_id = u.id
				AND u.facility = f.facility_code
				AND f.district = d.id
				AND d.county = c.id
				AND c.id =$option_id");		
			break;
			
			default:
			break;
		}
		
	}
	
	public static function update_log_out_action($user_id)
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->execute("update log set `end_time_of_event`=NOW(),action='Logged Out' where `user_id`='$user_id' and UNIX_TIMESTAMP( `end_time_of_event`) =0");	
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->execute("
		update  `log`  set  `action` =  'Logged Out' and `end_time_of_event`=NOW()
		WHERE  `action` =  'Logged In'
		AND  `start_time_of_event` < NOW( ) - INTERVAL 1  DAY 
		AND UNIX_TIMESTAMP( `end_time_of_event`) =0");	
	}
	
	public static function log_out_inactive_people()
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->execute("
		update  `log`  set  `action` =  'Logged Out' and `end_time_of_event`=NOW()
		WHERE  `action` =  'Logged In'
		AND  `start_time_of_event` < NOW( ) - INTERVAL 1  DAY 
		AND UNIX_TIMESTAMP( `end_time_of_event`) =0");		
	}
	public static function log_user_action($user_id, $user_action = NULL)
	{
		switch ($user_action):
			case 'issue':
				$action = 'issued = 1';
			break;
			case 'order':
				$action = 'ordered = 1';
			break;
			case 'decommission':
				$action = 'decommissioned = 1';
			break;	
			case 'redistribute':
				$action = 'redistribute = 1';
			break;
			case 'add_stock':
				$action = 'add_stock = 1';
			break;
			default:
			break;
			endswitch;
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->execute("
			update log set $action, 
			where `user_id`='$user_id'
			AND action = 'Logged In' 
			and UNIX_TIMESTAMP( `end_time_of_event`) =0");	
		 
	}
	public static function get_log_data($district_id = null,$county_id = null, $date)
	{
		//$year = date("Y");
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		select ifnull(count(l.issued), 0) as total_issues
		from log l, user u
		where l.user_id = u.id
		AND u.county_id = $county_id
		AND u.district = $district_id
		AND l.issued = 1
		AND DATE_FORMAT( l.`start_time_of_event` ,'%Y') = '$date'
		");
		$q1 = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		select ifnull(count(l.ordered), 0) as total_orders
		from log l, user u
		where l.user_id = u.id
		AND u.county_id = $county_id
		AND u.district = $district_id
		AND l.ordered = 1
		AND DATE_FORMAT( l.`start_time_of_event` ,'%Y') = '$date'
		");
		$q_2 = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		select ifnull(count(l.decommissioned), 0) as total_decommisions
		from log l, user u
		where l.user_id = u.id
		AND u.county_id = $county_id
		AND u.district = $district_id
		AND l.decommissioned = 1
		AND DATE_FORMAT( l.`start_time_of_event` ,'%Y') = '$date'
		");
		$q_3 = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		select ifnull(count(l.redistribute), 0) as total_redistributions
		from log l, user u
		where l.user_id = u.id
		AND u.county_id = $county_id
		AND u.district = $district_id
		AND l.redistribute = 1
		AND DATE_FORMAT( l.`start_time_of_event` ,'%Y') = '$date'
		");
		$q_4 = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		select ifnull(count(l.add_stock), 0) as total_stock_added
		from log l, user u
		where l.user_id = u.id
		AND u.county_id = $county_id
		AND u.district = $district_id
		AND l.add_stock = 1
		AND DATE_FORMAT( l.`start_time_of_event` ,'%Y') = '$date'
		");
		return array(
		'total_issues'=>$q[0]['total_issues'],
		'total_orders'=>$q1[0]['total_orders'],
		'total_decommisions'=>$q_2[0]['total_decommisions'],
		'total_redistributions'=>$q_3[0]['total_redistributions'],
		'total_stock_added'=>$q_4[0]['total_stock_added'],

		);
	}
	public static function get_subcounty_login_count($county_id,$district_id,$date)
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("SELECT 
			ifnull(COUNT(DISTINCT u.facility ),0) AS total
			FROM log l, user u
			WHERE u.id = l.user_id
			AND u.county_id = $county_id
			AND u.district = $district_id
			AND DATE_FORMAT( l.start_time_of_event,'%Y-%m-%d') = '$date'
			
			");
		return $q;
	}
	public static function get_county_login_count($county_id,$district_id,$date)
	{	
			$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
			SELECT ifnull(COUNT(DISTINCT u.facility ),0) AS total
			FROM log l, user u
			WHERE u.id = l.user_id
			AND u.county_id =$county_id
			AND u.district=$district_id
			AND DATE_FORMAT( l.start_time_of_event,'%Y-%m-%d') = '$date'
	
			");
			
			return $q;
	}

	public static function get_subcounty_login_monthly_count($county_id,$district_id,$date)
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
		SELECT  IFNULL( COUNT(DISTINCT u.facility) , 0 ) AS total
		FROM log l, user u
		WHERE u.id = l.user_id
		AND u.county_id =$county_id
		AND u.district = $district_id
		AND DATE_FORMAT( l.`start_time_of_event` ,'%Y-%m') = '$date'");
		return $q;
	
	
	}
	
	public static function get_county_login_monthly_count($county_id,$district_id,$date)
	{
		$q = Doctrine_Manager::getInstance()->getCurrentConnection()->fetchAll("
			SELECT  IFNULL( COUNT(DISTINCT u.facility) , 0 ) AS total
			FROM log l, user u
			WHERE u.id = l.user_id
			AND u.county_id =$county_id
			AND u.district =$district_id
			AND DATE_FORMAT( l.`start_time_of_event` ,'%Y-%m') = '$date'");
		return $q;
	
	
	}



}