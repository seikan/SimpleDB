<?php

// Include SimpleDB library
require_once 'class.SimpleDB.php';

// Open example.db as database
$db = new SimpleDB('example.db');

// Database not created yet
if (!$db->isCreated()) {
	// Create database
	$db->create([
		'user_id' => SimpleDB::TYPE_INT,
		'name' => SimpleDB::TYPE_STR,
		'email' => SimpleDB::TYPE_STR,
		'password' => SimpleDB::TYPE_STR,
		'date_created' => SimpleDB::TYPE_DATE,
	]);
}

// Set index key for auto increment
$db->setIndexKey('user_id');

$names = [
	'brian', 'charles', 'christopher', 'daniel', 'david', 'donald', 'edward', 'george', 'james', 'john', 'joseph', 'kenneth', 'mark', 'michael', 'paul', 'richard', 'robert', 'ronald', 'steven', 'thomas',
];

for ($i = 0; $i < 10; ++$i) {
	// Insert new record
	$name = ucwords($names[rand(0, count($names) - 1)]);

	$db->insert([
		'name' => $name,
		'email' => strtolower($name).rand(0, 999).'@example.com',
		'password' => substr(sha1(microtime()), 0, 12),
		'date_created' => 'NOW()',
	]);
}

// Change the email for user ID #5 to happy@example.com
$db->update('user_id', '=5', [
	'email' => 'happy@example.com',
]);

echo 'Updated '.$db->affectedRows().' records.<br />';

// Update user with name contains "es" to have email "es@example.com" and password "1234567890"
$db->update('name', 'es', [
	'email' => 'es@example.com',
	'password' => '1234567890',
]);

echo 'Updated '.$db->affectedRows().' records.<br />';

// Delete user with ID #3
$db->delete('user_id', '=3');

echo 'Deleted '.$db->affectedRows().' records.<br />';
