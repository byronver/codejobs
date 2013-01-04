<?php
/**
 * Access from index.php:
 */
if(!defined("_access")) {
	die("Error: You don't have permission to access here...");
}

class Jobs_Model extends ZP_Load {
	
	public function __construct() {
		$this->Db = $this->db();
		
		$this->language = whichLanguage();
		$this->table 	= "jobs";
		$this->fields   = "ID_Job, ID_User, Company, Title, Slug, Email, Company_Information, Location, Salary, Allocation_Time, Requirements, Experience, Activities, Profile, Technologies, Additional_Information, Company_Contact, Language, Duration, Situation";

		$this->Data = $this->core("Data");

		$this->Data->table($this->table);
	}

	public function getRSS() {	
		return $this->Db->findBySQL("Language = '$this->language' AND Situation = 'Active'", $this->table, $this->fields, NULL, "ID_Post DESC");
	}

	
	public function cpanel($action, $limit = NULL, $order = "Language DESC", $search = NULL, $field = NULL, $trash = FALSE) {
		if($action === "edit" or $action === "save") {
			$validation = $this->editOrSave($action);
		
			if($validation) {
				return $validation;
			}
		}
		
		if($action === "all") {
			return $this->all($trash, $order, $limit);
		} elseif($action === "edit") {
			return $this->edit();															
		} elseif($action === "save") {
			return $this->save();
		} elseif($action === "search") {
			return $this->search($search, $field);
		}
	}
	
	private function all($trash, $order, $limit) {	
		if(!$trash) { 
			return (SESSION("ZanUserPrivilegeID") === 1) ? $this->Db->findBySQL("Situation != 'Deleted'", $this->table, "ID_Job, Company, Title, Location, Language, Situation", NULL, $order, $limit) : $this->Db->findBySQL("ID_User = '". SESSION("ZanUserID") ."' AND Situation != 'Deleted'", $this->table, "ID_Job, Title, Location, Situation", NULL, $order, $limit);
		} else {

			return (SESSION("ZanUserPrivilegeID") === 1) ? $this->Db->findBy("Situation", "Deleted", $this->table, "ID_Job, Company, Title, Location, Language, Situation", NULL, $order, $limit) : $this->Db->findBySQL("ID_User = '". SESSION("ZanUserID") ."' AND Situation = 'Deleted'", $this->table, "D_Job, Title, Location, Situation", NULL, $order, $limit);
		}
	}
	
	private function editOrSave($action) {
		$validations = array(
			"company"   	=> "required",
			"title" 		=> "required",
			"email" 		=> "email?",
			"cinformation"  => "required",
			"location" 		=> "required",
			"salary"		=> "required",
			"requirements" 	=> "required",
			"experience" 	=> "required",
			"activities" 	=> "required",
			"profile" 		=> "required",
			"technologies" 	=> "required",
			"ccontact" 		=> "required"
		);
		 
		$this->helper(array("alerts", "time"));
		
		$date = now(4);

		$data = array(
			"ID_User" 	 => SESSION("ZanUserID"),
			"Slug"    	 => slug(POST("title", "clean")),
			"Start_Date" => $date,
			"End_Date"   => $date + POST("duration")
		);

		$this->Data->change("cinformation", "Company_Information");
		$this->Data->change("allocation", "Allocation_Time");
		$this->Data->change("ccontact", "Company_Contact");

		$this->data = $this->Data->proccess($data, $validations);

		if(isset($this->data["error"])) {
			return $this->data["error"];
		}
	}

	public function getByID($ID) {			
		return $this->Db->find($ID, $this->table, $this->fields);
	}
	
	private function save() {
		if($this->Db->insert($this->table, $this->data)) {
		 	return getAlert(__("The job has been saved correctly"), "success");
		}

		return getAlert(__("Insert Error"));
	}
	
	private function search($search, $field) {
		if($search and $field) {
			return ($field === "ID") ? $this->Db->find($search, $this->table) : $this->Db->findBySQL("$field LIKE '%$search%'", $this->table);	      
		} else {
			return FALSE;
		}
	}
	
	public function count($type = "posts") {					
		$year  = isYear(segment(1,  isLang())) ? segment(1, isLang()) : FALSE;
		$month = isMonth(segment(2, isLang())) ? segment(2, isLang()) : FALSE;
		$day   = isDay(segment(3,   isLang())) ? segment(3, isLang()) : FALSE;

		if($type === "posts") {									
			if($year and $month and $day) {
				$count = $this->Db->countBySQL("Language = '$this->language' AND Year = '$year' AND Month = '$month' AND Day = '$day' AND Situation = 'Active'", $this->table);
			} elseif($year and $month) {
				$count = $this->Db->countBySQL("Language = '$this->language' AND Year = '$year' AND Month = '$month' AND Situation = 'Active'", $this->table);
			} elseif($year) {
				$count = $this->Db->countBySQL("Language = '$this->language' AND Year = '$year' AND Situation = 'Active'", $this->table);
			} else {
				$count = $this->Db->countBySQL("Language = '$this->language' AND Situation = 'Active'", $this->table);
			}
		} elseif($type === "comments") {
			$count = 0;
		} elseif($type === "tag") {
			$data = $this->getByTag(segment(2, isLang()));
			
			$count = count($data);
		}
		
		return isset($count) ? $count : 0;
	}
	
	
	
	public function removePassword($ID) {
		$this->Db->update($this->table, array("Pwd" => ""), $ID);		
	}

	private function edit() {
		if($this->Db->update($this->table, $this->data, POST("ID"))) {
            
            return getAlert(__("The code has been edit correctly"), "success");
        }
        
        return getAlert(__("Update error"));
	}
	
}