
# WordPress Database Table Tools

A package to aid in WordPress theme and plugin development by adding classes that quickly allow you to add and manage custom tables to the WordPress database. This uses native WordPress API functions and makes it easy to setup model based I/O using abstract parent classes.

## PHP Version Requirements

Version 1 of this library should work on **PHP 7.4** or newer.


## How to Install

#### Composer Install
```sh
composer require claytonkreisel/wpdb-tools
```
or

#### Manual Install

Download the package and manually place it in your application then simply include the ```autoload.php``` file like this at the beginning of your application:
```php
<?php

	include "PATH/TO/wpdb-tools/autoload.php";

?>
```

## How to Use

### Custom Tables

This library contains a parent abstract class of ```Table```. This class allows you to create a child class that will manage your database columns using an array defined in a child method and provides you with methods to ```insert```, ```select```, ```update``` and ```delete``` rows within that table.

#### Creating a Custom Table Class

In order to create a custom table you will create a child class that extends the ```Table``` class within this package.

***NOTE:*** If you wish to create a table that also has a relational "metadata" table much like the ```post``` and ```postmeta``` structure native to WordPress then please refer to the ```TableMeta``` class. This class will simply create a single table without a corresponding meta table. That class will create and manage both the main table and the metadata table without the need of defining a ```Table``` class.

To create a ``Table`` you will put the following code in your ``functions.php`` file or another file that is included in your plugin or theme. This assumes you have already installed or included the classes through composer or manually.

```php
<?php

	use WPDBTools\CustomTable\Table;

	class Your_Table_Name extends Table{

		/*
			This version number is stored in the wp_options table and tells
			WordPress when to check for updated structure. Be sure to pass a
			string with proper versioning xx.xx.xx.
		*/
		public function version(){
			return "0.0.1";
		}

		/*
			Return a name for the table. This will be the name of the table
			after the WordPress prefix is added. IE "test_name" becomes a table
			with the name "wp_test_name" in the WordPress database.
		*/
		public function name(){
			return 'your_table_name';
		}

		/*
			Return an associative array of columns that defines the table structure in
			the database. The formatting for this is extremely important and it will
			require you to have an understanding of how to write typicall mysql table
			creation syntax.

			The key of each array iteration is the name of the column and the value is
			the creation rules.
		*/
		public function columns(){
			return [
				'id' => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT',
        'int_column' => 'int(11) UNSIGNED NOT NULL DEFAULT 0',
        'varchar_column' => 'varchar(15) NOT NULL',
        'varchar_column_2' => 'varchar(30) NOT NULL DEFAULT "Default Stuff"',
        'boolean_column' => 'boolean NOT NULL DEFAULT 0',
        'text_column' => 'text NOT NULL',
        'longtext_column' => 'longtext NOT NULL',
        'datetime_column' => 'datetime NOT NULL DEFAULT "2000-01-01 12:00:00"'
			]
		}

		/*
			This function should return a string that defines which key in your database
			columns array you wish to define as the primary key for your database table.
			This will typically be the first column (IE "id").
		*/
		public function primary_key(){
			return "id";
		}

	}

?>
```
After you have written this code you can now create a new instance of this class. Upon construction this class will fire a method that checks to see if the version of the database has changed. If it has then a database altering and cleanup will occur.

***NOTE:*** Columns that are removed from the columns array method will remain in the database until you explicitly remove them using the ```remove_column``` method. This is to protect from accidental deletion of data.

```php
<?php

	/*
		Initiates the new table and performs a check on the database in order to update
		structure if needed.
	*/
	$your_new_table = new Your_Table_Name();

?>
```
#### Inserting a Row in a Custom Table
In order to insert a new row into a custom table you simply call the ```insert``` method on the object you initiated.
```php
<?php

	/*
		This method inserts one new row into the database.

		@params $data(array)[required] - An associative array using the column names as keys
		and values as the column value.
	*/
	$your_new_table->insert([
		'int_column' => 30,
    'varchar_column' => 'Short Value',
    'boolean_column' => true,
    'text_column' => '',
    'longtext_column' => 'This is a considerable amount of text that will go into the database',
    'datetime_column' => '2021-07-01 10:30:00'
        //Not all columns are required in order to insert data...
	]);

?>
```
#### Inserting Multiple Rows in a Custom Table
In order to insert multiple rows into a custom table you simply call the ```insert``` method on the object you initiated with multiple associative arrays of data as the ```$data``` parameter.

***NOTE:*** In order for this method to work properly the same keys must be passed in each iteration of the associative array.
```php
<?php

	/*
		This method inserts multiple new rows into the database.

		@params $data(array)[required] - An associative array of arrays using the column names
		as keys and values as the column value.
	*/
	$your_new_table->insert([
		[
			'int_column' => 30,
      'varchar_column' => 'Short Value',
      'boolean_column' => true,
      'text_column' => '',
      'longtext_column' => 'This is a considerable amount of text that will go into the database',
      'datetime_column' => '2021-07-01 10:30:00'
    ],
    [
	    'int_column' => 55,
      'varchar_column' => 'Some other value',
      'boolean_column' => false,
      'text_column' => 'This one has some text here',
      'longtext_column' => '',
      'datetime_column' => '2021-07-04 16:30:00'
    ]
	]);

?>
```
#### Selecting Rows from Custom Tables
There are multiple ways to select data from your custom table. For more complex selections you can write your own ```WHERE``` statement or use an array driven method in order to make your syntax cleaner. Either way you will only need to use one of three methods in order to select data; ```select_all```, ``select`` or ``select_or``.
##### Using the ```select```, ```select_or``` and ```select_all``` Methods
The ```select``` method gives you the most versatility in your data selection approach. This method takes 3 parameters at present: ```$data```, ```$order``` and ```$return_type```.

The ```select_all``` simply returns all the rows in the table. This method only takes the last 2 parameters at present: ```$order``` and ```$return_type```.

 - ```$data```*(array|string)* - [Required]
	 - Can be an associative array, an array of arrays (view example for more definition), or a ```WHERE``` string in SQL syntax.
	 - ***NOTE:*** Not present in the ```select_all``` method.
 - ```$order```*(array)``` - [Optional]  | Default: false
	 - Can be an associative array or an array of arrays.
 - ```$return_type```*(php_obj) - [Optional] | Default: ARRAY_A
	 - The way that you would like your row returned to you. Options include ```ARRAY_A```, ```ARRAY_N```, ```OBJECT``` and ```OBJECT_K```.

Below are examples of various types of selects:
```php
<?php

	/* Simply select all rows in the table */
	$your_new_table->select_all();
	//Output -> SELECT * FROM your_new_table;
	//Returns-> All rows from the table


	/* Simple select with string for WHERE clause */
	$your_new_table->select([
		'id' => 20
	]);
	//Output -> SELECT * FROM your_new_table WHERE `id` = 20;
	//Returns -> Row with an ID of 20


	/* Simple select with multiple WHERE clauses with AND operator */
	$your_new_table->select([
		'varchar_column' => 'Short Value',
		'boolean_column' => true,
	]);
	//Output -> SELECT * FROM your_new_table WHERE `varchar_column` = "Short Value" AND `boolean_column` = 1;
	//Return -> Any row that has a varchar_column of "Short Value" and boolean_column of true


	/* Simple select with multiple WHERE clauses with OR operator */
	$your_new_table->select_or([
		'varchar_column' => 'Short Value',
		'boolean_column' => true,
	]);
	//Output -> SELECT * FROM your_new_table WHERE `varchar_column` = "Short Value" OR `boolean_column` = 1;
	//Return -> Any row that has a varchar_column of "Short Value" or boolean_column of true


	/* Complex AND select using operators other than = (this works with select_or as well) */
	/*
		Provide an array of arrays that will build the WHERE clause of your query
			@params key(required) - The column name
			@params value(required) - The value you are comparing against
			@params compare(optional) - The comparison operator (=,!=,<,>,<=,>=,LIKE,%LIKE%,%LIKE,LIKE%). Defaults to "=".
			@params operator(optional) - The operator (AND or OR) that will connect this rule to the following rule. Only applies if there is another array in the set. Defaults to "AND".
	*/
	$your_new_table->select([
		[
			"key" => "boolean_column",
			"value" => true
		],
		[
			"key" => "datetime_column",
			"value" => "2020-01-01 12:00:00",
			"compare" => "<",
			"operator" => 'OR'
		],
		[
			"key" => "longtext_column",
			"value" => "some text",
			"compare" => "%LIKE%"
		],
		[
			"key" => "text_column",
			"value" => "another text",
			"compare" => "LIKE%"
		]
	]);
	//Output -> SELECT * FROM your_new_table WHERE `boolean_column` = 1 AND `datetime_column` < "2020-01-01 12:00:00" OR `longtext_column` LIKE "%some text%" AND `text_column` LIKE "another text%";
	//Return -> Any row that has a boolean_column of true and where the datetime_column is less than January 1, 2020 at noon and where the longtext_column contains the string "some text" anywhere in the cell and where text_column begins with "another text" regarless of what comes after it in the cell.


	/* Simply select all with ORDER clause */
	$your_new_table->select_all([
		'orderby' => 'varchar_column',
		'order' => 'ASC',
	]);
	//Output -> SELECT * FROM your_new_table ORDER BY `varchar_column` ASC;
	//Return -> Returns all rows sorted by the varchar_column in ASC (1-9,A-Z) order.


	/* Simple select with multiple ORDER clauses */
	$where = [
		'boolean_column' => false
	];
	$order = [
		[
			'orderby' => 'datetime_column',
			'order' => 'DESC'
		],
		[
			'orderby' => 'varchar_column',
			'order' => 'ASC',
			'is_numeric' => true
		],
	];
	$your_new_table->select($where, $order);
	//Output -> SELECT * FROM your_new_table WHERE `boolean_column` = 0 ORDER BY `datetime_column` DESC, ABS(`varchar_column`) ASC;
	//Return -> Returns all rows where the boolean_column is set to false and then sorted by the datetime_column in DESC (newest - oldest) order first followed by varchar_column in ASC (1-9,A-Z) order.


	/* Change the type of return you get from an associative array to a keyed object */
	$your_new_table->select_all(false, OBJECT_K);
	//Output -> SELECT * FROM your_new_table;
	//Return -> Returns every row in the table formatted as an object with the column names as keys

?>
```

#### Deleting Rows in a Custom Table
In order to delete rows in a custom table you only need to use the ```delete``` method on the table object. This method only takes one parameter ```$data``` which serves as the ```WHERE``` clause. This parameter can be an associative array, an array of arrays or a string.

The following examples show different ways to use this method:
```php
<?php

	/* Delete single row based on id */
	$your_new_table->delete([
		'id' => 1
	]);
	//Output -> DELETE FROM your_new_table WHERE id = 1;
	//Result -> Deletes the row with the id of 1 from the table.


	/* Delete rows using multiple definitions glued with AND */
	$your_new_table->delete([
		'boolean_column' => true,
		'varchar_column' => 'Some text'
	]);
	//Output -> DELETE FROM your_new_table WHERE `boolean_column` = 1 AND `varchar_column` => "Some text";
	//Result -> Deletes all rows where the boolean_column is true and the varchar_column is "Some text".


	/* Delete rows using multiple definitions glued with AND and other operaters for comparison */
	/*
		Provide an array of arrays that will build the WHERE clause of your query
			@params key(required) - The column name
			@params value(required) - The value you are comparing against
			@params compare(optional) - The comparison operator (=,!=,<,>,<=,>=,LIKE,%LIKE%,%LIKE,LIKE%). Defaults to "=".
			@params operator(optional) - The operator (AND or OR) that will connect this rule to the following rule. Only applies if there is another array in the set. Defaults to "AND".
	*/
	$your_new_table->delete([
		[
			'key' => 'boolean_column',
			'value' => true
		],
		[
			'key' => 'varchar_column',
			'value' => 'Some text',
			'compare' => '!=',
			'operator' => 'OR'
		],
		[
			'key' => 'longtext_column',
			'value' => 'look for this',
			'compare' => '%LIKE%'
		]
	]);
	//Output -> DELETE FROM your_new_table WHERE `boolean_column` = 1 AND `varchar_column` != "Some text" OR `longtext_column` LIKE "%look for this%";
	//Result -> Deletes all rows where the boolean_column is true and the varchar_column does not "Some text" and the longtext_column contains the string "look for this".


	/* Delete rows based on where cause passed in string */
	$your_new_table->delete("`id` > 50 OR (`id` <= 50 AND `varchar_column` = 'Some Text')");
	//Output -> DELETE FROM your_new_table WHERE `id` > 50 OR (`id` <= 50 AND `varchar_column` = 'Some Text');
	//Result -> Deletes a row with an id greater than 50 or with an id of 50 or less if the varchar_column is equal to "Some text".


	/* In the rare even you want to deletes all rows in the table */
	//WARNING THIS WILL DELETE ALL DATA IN THE TABLE
	$your_new_table->delete_all();

?>
```

#### Updating Rows in a Custom Table
In order to update rows in a custom table you only need to use the ```update``` method on the table object. This method  takes two parameters; ```$data``` which serves as the ```SET``` clause of the query and ```$where``` which serves as the  ```WHERE``` clause.

The ```$data``` parameter is an associative array where the key serves as the table column and the value serves as the new value you wish to update to.

The ```$where``` parameter can be an array, an array of arrays or a string. This will define which rows are updated with the ```$data```.

The following examples will help you know how to properly use the ```update``` method:
```php
<?php

	/* Simple update using the row's ID */
	$your_new_table->update(
		[
			'longtext_column' => 'This is a line of new text to go into the cell',
			'datetime_column' => '2021-01-01 12:00:00'
		],
		[
			'id' => 35
		]
	);
	//Output-> UPDATE your_new_table SET `longtext_column` = 'This is a line of new text to go into the cell', `datetime_column` = '2021-01-01 12:00:00' WHERE `id` = 35;
	//Result-> Updates the row with an ID of 35 and changes two values in that row. The longtext_column and the datetime_column are both changed with their respective new values. NOTE: you can change as many or as few row values as you would like.


	/* Multiple WHERE clause matches */
	$your_new_table->update(
		[
			'longtext_column' => 'This is a line of new text to go into the cell',
			'datetime_column' => '2021-01-01 12:00:00'
		],
		[
			'varchar_column' => "Some value",
			'boolean_column' => true,
			'int_column' => 100
		]
	);
	//Output-> UPDATE your_new_table SET `longtext_column` = 'This is a line of new text to go into the cell', `datetime_column` = '2021-01-01 12:00:00' WHERE `varchar_column` = "Some value" AND `boolean_column` = 1 AND `int_column` = 100;
	//Result-> Updates the rows where the varchar_column is equal to "Some value", the boolean_column is true and the int_column is equal to 100. Change two values in that row. The longtext_column and the datetime_column are both changed with their respective new values. NOTE: you can change as many or as few row values as you would like.


	/* Build a more complex WHERE clause for the update that includes custom operators and comparisons */
	$your_new_table->update(
		[
			'longtext_column' => 'This is a line of new text to go into the cell',
			'datetime_column' => '2021-01-01 12:00:00'
		],
		[
			[
				'key' => 'boolean_column',
				'value' => true
			],
			[
				'key' => 'varchar_column',
				'value' => 'Some text',
				'compare' => '!=',
				'operator' => 'OR'
			],
			[
				'key' => 'longtext_column',
				'value' => 'look for this',
				'compare' => '%LIKE%'
			]
		]
	);
	//Output-> UPDATE your_new_table SET `longtext_column` = 'This is a line of new text to go into the cell', `datetime_column` = '2021-01-01 12:00:00' WHERE `boolean_column` = 1 AND `varchar_column` != "Some value" OR `longtext_column` LIKE '%look for this%';
	//Result-> Updates the rows where the boolean_column is true and either the varchar_column does not equal "Some text" or the 'longtext_column' contains the string "look for this". Change two values in that row. The longtext_column and the datetime_column are both changed with their respective new values. NOTE: you can change as many or as few row values as you would like.


	/* Simple update using a string for the WHERE clause */
	$your_new_table->update(
		[
			'longtext_column' => 'This is a line of new text to go into the cell',
			'datetime_column' => '2021-01-01 12:00:00'
		],
		"`id` > 35 OR id < 20"
	);
	//Output-> UPDATE your_new_table SET `longtext_column` = 'This is a line of new text to go into the cell', `datetime_column` = '2021-01-01 12:00:00' WHERE `id` > 35 OR `id` < 20;
	//Result-> Updates the row with an ID of 35 and changes two values in that row. The longtext_column and the datetime_column are both changed with their respective new values. NOTE: you can change as many or as few row values as you would like.

?>
```

### Altering a Custom Table's Structure

#### Adding a Column or Changing Attributes for an Existing Column

In order to add or change an existing column in a custom database we only need to utilize the power of the ```columns``` function in our ```Table``` child object that we have created. This function uses the ```dbDelta``` tool that is native to WordPress in order to alter tables. So all we need to do is update our ```return``` value in the ```columns``` method in order to alter our table.

***Note:*** You cannot remove a column this way. You need to look at the ```remove_column``` method for more information on how to do that.

Below are some examples:
```php

/* ORIGINAL STRUCTURE */
public function columns(){
	return [
		'id' => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT',
		'int_column' => 'int(11) UNSIGNED NOT NULL DEFAULT 0',
        'varchar_column' => 'varchar(15) NOT NULL',
        'varchar_column_2' => 'varchar(30) NOT NULL DEFAULT "Default Stuff"',
        'boolean_column' => 'boolean NOT NULL DEFAULT 0',
        'text_column' => 'text NOT NULL',
        'longtext_column' => 'longtext NOT NULL',
        'datetime_column' => 'datetime NOT NULL DEFAULT "2000-01-01 12:00:00"'
	]
}

/* ADD A COLUMN */
//To add a boolean column to your table called "boolean_column_2" where the default is set to true simply change your columns method to the following.
public function columns(){
	return [
		'id' => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT',
		'int_column' => 'int(11) UNSIGNED NOT NULL DEFAULT 0',
    'varchar_column' => 'varchar(15) NOT NULL',
    'varchar_column_2' => 'varchar(30) NOT NULL DEFAULT "Default Stuff"',
    'boolean_column' => 'boolean NOT NULL DEFAULT 0',
    'text_column' => 'text NOT NULL',
    'longtext_column' => 'longtext NOT NULL',
    'datetime_column' => 'datetime NOT NULL DEFAULT "2000-01-01 12:00:00"',
    'boolean_column_2' => 'boolean NOT NULL DEFAULT 1'
	]
}

/* CHANGE AN EXISTING COLUMN */
//In order to change the boolean_column to have a default of true and to change the length of varchar_column to 25 simply change your columns method to the following.
public function columns(){
	return [
		'id' => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT',
		'int_column' => 'int(11) UNSIGNED NOT NULL DEFAULT 0',
    'varchar_column' => 'varchar(25) NOT NULL',
    'varchar_column_2' => 'varchar(30) NOT NULL DEFAULT "Default Stuff"',
    'boolean_column' => 'boolean NOT NULL DEFAULT 1',
    'text_column' => 'text NOT NULL',
    'longtext_column' => 'longtext NOT NULL',
    'datetime_column' => 'datetime NOT NULL DEFAULT "2000-01-01 12:00:00"'
	]
}
```

#### Removing a Column

In order to remove a column from the database you need to utilize the ```remove_column``` method on the table object that you created earlier. Although not necessary it is also highly recommended that you update your tables ```columns``` method in order to reflect the changes of the now deleted column. Once the column has been deleted you no longer need to invoke the ```remove_column``` method.

The ```remove_column``` method accepts one parameter of ```$column_name```. This parameter is the name of the column you wish to ```DROP``` from the table. Once this is done it cannot be undone, so please make any backups or data migrations you need to before doing this.

***NOTE:*** Yes, I know I just told you this above but it is worth rementioning. This method uses the ```DROP``` command on the column. You will lose all data in that column. You must either backup or migrate the data to another source if you wish to retain that information. ***Once it is dropped it is gone for good!***

Below is an example on how to use this method:

```php
<?php

	//Drops the longtext_column column from the table
	$your_new_table->remove_column("longtext_column");

	//After this please remove the longtext_column from your columns method in the Table child class you created earlier.
?>
```
