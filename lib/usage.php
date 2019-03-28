<?php

	include_once('mysql.php');
	
	$mysql = new Mysql('localhost', 'root', 'xujiwei-', 'Account');
	
	// get all posts
	/*
	try{
		$posts = $mysql->get('user_bill');
		print_r($posts);
		echo $mysql->num_rows(); // number of rows returned
	}catch(Exception $e){
		echo 'Caught exception: ', $e->getMessage();
	}
	
	// get all post titles and authors
	try{
		$posts = $mysql->get('user_bill', array('user_id', 'pay'));
		// or
		//$posts = $mysql->get('posts', 'title,author');
		print_r($posts);
		echo $mysql->last_query(); // the raw query that was ran
	}catch(Exception $e){
		echo 'Caught exception: ', $e->getMessage();
	}
	 */
	// get one post
	try{
		$post = $mysql->limit(1)->where('user_uuid','2233')->get('user','user_uuid');
		print_r($post);
	}catch(Exception $e){
		echo 'Caught exception: ', $e->getMessage();
	}
/*	
	// get post with an id of 1
	try{
		$post = $mysql->where('id', 3)->get('user_bill');
		// or
		//$post = $mysql->where(array('id', 1))->get('posts');
		print_r($post);
	}catch(Exception $e){
		echo 'Caught exception: ', $e->getMessage();
	}
 */
	
	// get all posts by the author of "John Doe"
	/*
	try{
		$posts = $mysql->where(array('user_id' => '13sdd'))->get('user_bill');
		print_r($posts);
	}catch(Exception $e){
		echo 'Caught exception: ', $e->getMessage();
	}
	 */
	// insert post
	try{
		$mysql->insert('user_bill',  ['user_id'=>'sdf', 'info'=>'sdf','category'=>1,'pay'=>32.1]);
		echo $mysql->insert_id(); // id of newly inserted post
	}catch(Exception $e){
		echo 'Caught exception: ', $e->getMessage();
	}
	/*
	// update post 1
	try{
		$mysql->where('id', 1)->update('user', array('user_uuid' => 'New Title'));
	}catch(Exception $e){
		echo 'Caught exception: ', $e->getMessage();
	}
	
	
	// delete post 1
	try{
		$mysql->where('id', 1)->delete('user');
	}catch(Exception $e){
		echo 'Caught exception: ', $e->getMessage();
	}
	 */
