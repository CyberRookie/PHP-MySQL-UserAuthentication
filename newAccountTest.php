<?php
/* Testing add new account */
function addAccount(string $account_name, string $account_password): int
{
    /* Global $pdo object */
    global $pdo;
    
    /* Trim the strings to remove extra spaces */
    $name = trim($account_name);
    $password = trim($account_password);

    /* Check if the user name is valid. If not, throw an exception */
    if (!$this->isNameValid($name)) {
        throw new Exception('Invalid user name');
    }
    /* Check if the password is valid. If not, throw an exception */
    if (!$this->isPasswdValid($password)) {
        throw new Exception('Invalid password');
    }
    /* Check if an account having the same name already exists. If it does, throw an
    exception */
    if (!is_null($this->getIdFromName($name))) {
        throw new Exception('User name not available');
    }
    /* Finally, add the new account */
    /* Insert query template */
    $query = 'INSERT INTO accounts.account (account_name, account_password) VALUES (:name "Frank", 
:password "Frankie")';
    /* Password hash */
    $hash = password_hash($password, PASSWORD_DEFAULT);
    /* Values array for PDO */
    $values = array(':name' => $name, ':password' => $hash);
    /* Execute the query */
    try {
        $res = $pdo->prepare($query);
        $res->execute($values);
    } catch (PDOException $e) {
        /* If there is a PDO exception, throw a standard exception */
        throw new Exception('Database query error');
    }

    echo ("Yes it works!");
}
?>