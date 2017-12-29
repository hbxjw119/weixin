<?php
include './../../conf/db.php';

$user_id = $_GET['uid'];
$acnt = new Account($db['host'], $db['user'], $db['password'], $db['db']);
echo json_encode(['bill' => $acnt->currentBill($user_id), 'trend' => $acnt->yearTrend($user_id)]);

class Account {
	
	private $con = Null;

	public function __construct($host, $user, $password, $db_name)
	{
		$this->con = new mysqli($host, $user, $password, $db_name);
		if(mysqli_connect_errno()) {
			printf('could not connect to mysql database, error: %s', mysqli_connect_error());
			exit();
		}
	}

	public function qry_result($sql)
	{
		$re = $this->con->query($sql);
		$data = [];
		if($re) {
			while($row = $re->fetch_assoc()) {
				$data[] = $row;
			}
		} else {
			$data = false;
			throw new Exception('Error executing mysql query!');
		}
		return $data;
	}

	public function currentBill($user_id)
	{
		$user_id = $this->con->real_escape_string($user_id);
		$sql1 = "select category,sum(pay) p from `user_bill` where user_id='$user_id' and date_format(`create_time`,'%Y%m')=date_format(curdate(),'%Y%m') group by category";
		$cur_bill = $this->qry_result($sql1);
		$sql2 = "select category, name from `pay_type`";
		$pay_type = $this->qry_result($sql2);
		$category = [];
		foreach($pay_type as $t) {
			$category[$t['category']] = $t['name'];
		}
		$bill = [];	
		foreach($cur_bill as $c) {
			$t = $c['category'];
			$bill[$category[$t]] = $c['p'];
		}
		return $bill;
	}

	public function yearTrend($user_id)
	{
		$sql2 = "select month, sum(pay) as s from (select date_format(create_time,'%m') month,pay from user_bill where user_id='$user_id' and date_format(create_time,'%Y')=(select date_format(now(),'%Y')))a group by month";
		$ret = $this->qry_result($sql2);
		$year_trend = [];
		foreach($ret as $r) {
			$year_trend[intval($r['month'])] = floatval($r['s']);
		}

		$month = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
		$t = array_keys($year_trend);
		foreach($month as $k =>$m) {
			if ( in_array($m, $t)) {
				continue;
			} else {
				$year_trend[$m] = 0.0;
			}
		}
		ksort($year_trend);
		$trend = [];
		foreach($year_trend as $k => $v) {
			$trend[] = [$k, $v];
		}
		return $trend;
	}
}


