<?php

class ARMS {
	public $db;

	public function __construct() {
		$this->db = new Database();
		$dsn = DB_TYPE."://".DB_USER.":".DB_PASS."@".DB_SERVER.":".DB_PORT."/".DB_NAME;
		$this->db->connect($dsn);
	}
	
	public function getReservation($reservation_id)
	{
		// load reservation from database
		$reservation_db_res = $this->db->query("select * from reservations where id like '$reservation_id' limit 1");
		$reservation_db_res->fetchInto($reservation_db_obj);
		$reservation = new Reservation();
	    $reservation->id = $reservation_db_obj->id;
        $reservation->sched_start_time = $reservation_db_obj->sched_start_time;
	    $reservation->sched_end_time = $reservation_db_obj->sched_end_time;
	    $reservation->key_checkout_time = $reservation_db_obj->key_checkout_time;
	    $reservation->key_checkin_time = $reservation_db_obj->key_checkin_time;
	    $reservation->key_checkin_by = $reservation_db_obj->key_checkin_by;
	    $reservation->num_attendees = $reservation_db_obj->num_attendees;
		$reservation->Room = $this->getRoom($reservation_db_obj->room_id);
		$reservation->User = $this->getUser($reservation_db_obj->user_id);
		return($reservation);
	}
	
	public function getRoom($room_id) {
	
		// load room from database
		$room_db_res = $this->db->query("select * from study_rooms where id like '$room_id' limit 1");
		$room_db_res->fetchInto($room_db_obj);
	
		$room = new Room();
		$room->id = $room_db_obj->id;
		$room->room_number = $room_db_obj->room_number;
		$room->room_name = $room_db_obj->room_name;
		$room->room_description = $room_db_obj->room_description;
		$room->capacity = $room_db_obj->capacity;
		$room->image1 = $room_db_obj->image1;
		$room->image2 = $room_db_obj->image2;
		$room->min_res_len_min = $room_db_obj->min_res_len_min;
		$room->max_res_len_min = $room_db_obj->max_res_len_min;
		$room->out_of_order = $room_db_obj->out_of_order;
		
		// load room amenities
		$amenity_db_res = $this->db->query("SELECT amenities.id,name,description,quantity FROM amenities,study_rooms_amenities where study_rooms_amenities.room_id like '$room->id' and study_rooms_amenities.amenity_id = amenities.id and study_rooms_amenities.active like '1' and amenities.active like '1' order by name");
		while($amenity_db_res->fetchInto($amenity_db_obj))
		{
			$amenity = new Amenity();
			$amenity->id = $amenity_db_obj->id;
			$amenity->name = $amenity_db_obj->name;
			$amenity->description = $amenity_db_obj->description;
			$room->amenities[] = $amenity;
		}
		
		return($room);
	}
	
	public function getUser($user_id) {

		$user_db_res = $this->db->query("select * from users where id like '$user_id' limit 1");
		$user_db_res->fetchInto($user_db_obj);

		$user = new User();
		$user->id = $user_db_obj->id;
		$user->patron_id = $user_db_obj->patron_id;
		$user->first_name = $user_db_obj->first_name;
		$user->last_name = $user_db_obj->last_name;
		$user->email = $user_db_obj->email;
		
		// TODO: load roles
		
		return($user);
	}
	
	
}

class Reservation {

    public $id;
    public $sched_start_time;
    public $sched_end_time;
    public $key_checkout_time;
    public $key_checkin_time;
    public $key_checkin_by;
    public $num_attendees;

    // constructor
    public function __construct($id=null,$sched_start_time=null,$sched_end_time=null,$key_checkout_time=null,$key_checkin_time=null,$key_checkin_by=null,$num_attendees=null) {
		$this->id = $id;
        $this->sched_start_time = $sched_start_time;
	    $this->sched_end_time = $sched_end_time;
	    $this->key_checkout_time = $key_checkout_time;
	    $this->key_checkin_time = $key_checkin_time;
	    $this->key_checkin_by = $key_checkin_by;
	    $this->num_attendees = $num_attendees;
		$this->Room = new Room();
		$this->User = new User();
    }
}

class Room {

	public $id;
	public $room_number;
	public $room_name;
	public $capacity;
	public $image1;
	public $image2;
	public $min_res_len_min;
	public $max_res_len_min;
	public $out_of_order;
	public $amenities;

	public function __construct($id=null,$room_number=null,$room_name=null,$capacity=null,$image1=null,$image2=null,$min_res_len_min=DEFAULT_MIN_RES_LEN,$max_res_len_min=DEFAULT_MAX_RES_LEN,$out_of_order=null) {
	
		$this->id = $id;
		$this->room_number = $room_number;
		$this->room_name = $room_name;
		$this->room_description = $room_description;
		$this->capacity = $capacity;
		$this->image1 = $image1;
		$this->image2 = $image2;
		$this->min_res_len_min = $min_res_len_min;
		$this->max_res_len_min = $max_res_len_min;
		$this->out_of_order = $out_of_order;
		$this->amenities = array();
	}
}


class Amenity {

	public $id;
	public $name;
	
	public function __construct($id=null,$name=null)
	{
		$this->id = $id;
		$this->name = $name;
		$this->description = $description;
		
	}
}

class User {

	public $id;
	public $patron_id;
	public $first_name;
	public $last_name;
	public $email;

	public function __construct($id=null,$patron_id=null,$first_name=null,$last_name=null,$email=null) {
		$this->id = $id;
		$this->patron_id = $patron_id;
		$this->first_name = $first_name;
		$this->last_name = $last_name;
		$this->email = $email;
	}

}

class Fine {

}

class FineReduction {

}

class Cancellation {

}


?>