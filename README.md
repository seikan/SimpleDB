

# SimpleDB

This is a very simple file based database written in PHP. It stores data under CSV format in a plain text file. This is not recommended for multi user application.



## Usage

### Configuration

Opens  a text file as SimpleDB. Due to security issues, you may use a hard-to-guess file name to prevent download access to your database file.

> \$db = new SimpleDB( **string** $database );

```php
$db = new SimpleDB('example.csv');
```



### Check Database

Checks if database already created in the file.

> **bool** \$db->isCreated( );

```php
if (!$db->isCreated()) {
	// Create database
}
```



### Create Database

Creates database by provides an array of `column name` and `data type`.

> **bool** \$db->create( **array** $fields );

**Data Type**

| Type                | Description     |
| ------------------- | --------------- |
| SimpleDB::TYPE_INT  | Integer.        |
| SimpleDB::TYPE_STR  | String or text. |
| SimpleDB::TYPE_DATE | Date time.      |

```php
// Create a database to store user data
$db->create([
	'user_id'        => SimpleDB::TYPE_INT,
	'name'           => SimpleDB::TYPE_STR,
	'email'          => SimpleDB::TYPE_STR,
	'password'       => SimpleDB::TYPE_STR,
	'date_created'   => SimpleDB::TYPE_DATE,
]);
```



### Index Key

Specifies a `column name` for unique indexing purpose. It must be a type of `SimpleDB::TYPE_INT`.

>  \$db->setIndexKey( **string** \$column_name );

```php
$db->setIndexKey('user_id');
```



### Insert

Inserts data into database.

> **bool** \$db->insert( **array** \$fields );

```php
$db->insert([
	'name'          => 'Skywalker',
	'email'         => 'skywalker@example.com',
  	'password'      => 'JzRRnTN34wKb',
	'date_created'  => 'NOW()',
]);
```



### Update

Updates a record.

> **bool** \$db->update( **string** \$column_name, **string** $needle, **array** \$fields );

```php
// Change the email to "test@example.com" for user with user_id #6
$db->update('user_id', '=6', [
	'email'	=> 'test@example.com',
]);

// Change the password to "12345678" for users with email contains "@example.com"
$db->update('email', '@example.com', [
	'password'	=> '12345678',
]);
```



### Select

Fetches records from database.

> **array** \$db->select( \[**string** \$column_name  = "\*"\]\[, **string** \$needle = "\*"\]\[, **string** \$order_by_column_name = ""\]\[, **int** \$sort = SimpleDB::ASC\] );

**Sort**

`SimpleDB::ASC` - Sort result by ascending order.

`SimpleDB::DESC` - Sort result by descending order.

```php
// Get all records
$rows = $db->select();

// Get records where any column contains "Lorem"
$rows = $db->select('*', 'Lorem');

// Get all records by date_created in descending order
$rows = $db->select('*', '*', 'date_created', SimpleDB::DESC);
```



### Delete

Deletes a record.

> **bool** \$db->delete( **string** \$column_name, **string** \$needle );

```php
// Delete user with user_id #2
$db->delete('user_id', '=2');

// Delete user with name contains "peter"
$db->delete('name', 'peter');
```



### Affected Rows

Gets affected rows after a query.

> **int** $db->affectedRows( );

```php
// Select user with name contains "peter"
$rows = $db->select('name', 'peter');

echo 'Total user:'.$db->affectedRows();

// Change the email to "test@example.com" for user with email contains "@example.com"
$db->update('email', '@example.com', [
    'email'	=> 'test@example.com',
]);

echo 'Total '.$db->affectedRows().' records has been updated.';
```



### Last ID

Gets the last inserts ID.

> **int** $db->getLastId( );

```php
// Insert a new user
$db->insert([
	'name'          => 'Skywalker',
	'email'         => 'skywalker@example.com',
  	'password'      => 'JzRRnTN34wKb',
	'date_created'  => 'NOW()',
]);

$userId = $db->getLastId();
```

