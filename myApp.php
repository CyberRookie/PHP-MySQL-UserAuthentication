<?php
/* 10/26/21 PHP Login and Authentication Tutorial from alexwebdevelop.com 
6:15 AM - REMOVED public function from ALL FUNCTIONS!*/
session_start();
/* use require './filename' If in different directory */
require 'db_inc.php';
require 'account_class.php';

$account = new Account();

try
{
	$newId = $account->addAccount('myNewName', 'myPassword');
}
catch (Exception $e)
{
    /* Something went wrong: echo the exception message and die */
    echo $e->getMessage();
    die();
    /* 10/27/21 6:00AM Moved curly bracket to end of file?? } */
}
echo 'The new account ID is ' . $newId;

/* Edit an account (selected by its ID). The name, the password and the status (enabled/disabled) can be changed */
function editAccount(int $id, string $name, string $password, bool $enabled)
{
	/* Global $pdo object */
	global $pdo;
	
	/* Trim the strings to remove extra spaces */
	$name = trim($name);
	$password = trim($password);
	
	/* Check if the ID is valid */
	if (!$this->isIdValid($id))
	{
		throw new Exception('Invalid account ID');
	}
	
	/* Check if the user name is valid. */
	if (!$this->isNameValid($name))
	{
		throw new Exception('Invalid user name');
	}
	
	/* Check if the password is valid. */
	if (!$this->isPasswdValid($password))
	{
		throw new Exception('Invalid password');
	}
	
	/* Check if an account having the same name already exists (except for this one). */
	$idFromName = $this->getIdFromName($name);
	
	if (!is_null($idFromName) && ($idFromName != $id))
	{
		throw new Exception('User name already used');
	}
	
	/* Finally, edit the account */
	
	/* Edit query template */
	$query = 'UPDATE myschema.accounts SET account_name = :name, account_password = :password, account_enabled = :enabled WHERE account_id = :id';
	
	/* Password hash */
	$hash = password_hash($password, PASSWORD_DEFAULT);
	
	/* Int value for the $enabled variable (0 = false, 1 = true) */
	$intEnabled = $enabled ? 1 : 0;
	
	/* Values array for PDO */
	$values = array(':name' => $name, ':password' => $hash, ':enabled' => $intEnabled, ':id' => $id);
	
	/* Execute the query */
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
	   /* If there is a PDO exception, throw a standard exception */
	   throw new Exception('Database query error');
	}
}

/* A sanitization check for the account ID */
function isIdValid(int $id): bool
{
	/* Initialize the return variable */
	$valid = TRUE;
	
	/* Example check: the ID must be between 1 and 1000000 */
	
	if (($id < 1) || ($id > 1000000))
	{
		$valid = FALSE;
	}
	
	/* You can add more checks here */
	
	return $valid;
}

/* Delete an account (selected by its ID) */
function deleteAccount(int $id)
{
    /* Global $pdo object */
    global $pdo;
    
    /* Check if the ID is valid */
    if (!$this->isIdValid($id)) {
        throw new Exception('Invalid account ID');
    }
    
    /* Query template */
    $query = 'DELETE FROM myschema.accounts WHERE account_id = :id';
    
    /* Values array for PDO */
    $values = array(':id' => $id);
    
    /* Execute the query */
    try {
        $res = $pdo->prepare($query);
        $res->execute($values);
    } catch (PDOException $e) {
        /* If there is a PDO exception, throw a standard exception */
        throw new Exception('Database query error');
    }

    /* Delete the Sessions related to the account */
    $query = 'DELETE FROM myschema.account_sessions WHERE (account_id = :id)';

    /* Values array for PDO */
    $values = array(':id' => $id);

    /* Execute the query */
    try {
        $res = $pdo->prepare($query);
        $res->execute($values);
    } catch (PDOException $e) {
        /* If there is a PDO exception, throw a standard exception */
        throw new Exception('Database query error');
    }
}

?>