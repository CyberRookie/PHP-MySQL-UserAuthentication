<?php
/* 10/27/21 Account class for the user login-authentication tutorial from
alexwebdevelop.com */

class Account
{
    /* Class properties (variables) */
    
    /* The ID of the logged in account (or NULL if there is no logged in account) */
    private $id;
    
    /* The name of the logged in account (or NULL if there is no logged in account) */
    private $name;

    /* TRUE if the user is authenticated, FALSE otherwise */
    private $authenticated;
    
    
    /* Public class methods (functions) */
    
    /* Constructor */
    public function __construct()
    {
        /* Initialize the $id and $name variables to NULL */
        $this->id = null;
        $this->name = null;
        $this->authenticated = false;
    }
    
    /* Destructor */
    public function __destruct()
    {
    }
    //}

    /* 10/27/21 - Not sure if this should be added INSIDE the ACCOUNT CLASS or NOT!
    Right now it's outside the main body of the account class. May need to move the account curly bracket to the end of the file.
    9:30AM I was right! Added bracket at end of file,now I get "databe query error"
    Add a new account to the system and return its ID (the account_id column of the accounts table) 6AM - Should I change variable name to account_name??*/
    public function addAccount(string $account_name, string $account_password): int
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
    
        /* Check if an account having the same name already exists. If it does, throw an exception */
        if (!is_null($this->getIdFromName($name))) {
            throw new Exception('User name not available');
        }
    
        /* Finally, add the new account */
    
        /* Insert query template */
        $query = 'INSERT INTO schema.account (account_name, account_password) VALUES (:name, :password)';
    
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
    
        /* Return the new ID */
        return $pdo->lastInsertId();
    }

    /* A sanitization check for the account username */
    public function isNameValid(string $name): bool
    {
        /* Initialize the return variable */
        $valid = true;
    
        /* Example check: the length must be between 8 and 16 chars */
        $len = mb_strlen($name);
    
        if (($len < 8) || ($len > 16)) {
            $valid = false;
        }
    
        /* You can add more checks here */
    
        return $valid;
    }

    /* A sanitization check for the account password */
    public function isPasswdValid(string $password): bool
    {
        /* Initialize the return variable */
        $valid = true;
    
        /* Example check: the length must be between 8 and 16 chars */
        $len = mb_strlen($password);
    
        if (($len < 8) || ($len > 16)) {
            $valid = false;
        }
    
        /* You can add more checks here */
    
        return $valid;
    }

    /* Returns the account id having $name as name, or NULL if it's not found */
    public function getIdFromName(string $name): ?int
    {
        /* Global $pdo object */
        global $pdo;
    
        /* Since this method is public, we check $name again here */
        if (!$this->isNameValid($name)) {
            throw new Exception('Invalid user name');
        }
    
        /* Initialize the return value. If no account is found, return NULL */
        $id = null;
    
        /* Search the ID on the database */
        $query = 'SELECT account_id FROM myschema.accounts WHERE (account_name = :name)';
        $values = array(':name' => $name);
    
        try {
            $res = $pdo->prepare($query);
            $res->execute($values);
        } catch (PDOException $e) {
            /* If there is a PDO exception, throw a standard exception */
            throw new Exception('Database query error');
        }
    
        $row = $res->fetch(PDO::FETCH_ASSOC);
    
        /* There is a result: get it's ID */
        if (is_array($row)) {
            $id = intval($row['account_id'], 10);
        }
    
        return $id;
    }

    /* Login with username and password */
    public function login(string $name, string $password): bool
    {
        /* Global $pdo object */
        global $pdo;
    
        /* Trim the strings to remove extra spaces */
        $name = trim($name);
        $passwd = trim($password);
    
        /* Check if the user name is valid. If not, return FALSE meaning the authentication failed */
        if (!$this->isNameValid($name)) {
            return false;
        }
    
        /* Check if the password is valid. If not, return FALSE meaning the authentication failed */
        if (!$this->isPasswdValid($password)) {
            return false;
        }
    
        /* Look for the account in the db. Note: the account must be enabled (account_enabled = 1) */
        $query = 'SELECT * FROM myschema.accounts WHERE (account_name = :name) AND (account_enabled = 1)';
    
        /* Values array for PDO */
        $values = array(':name' => $name);
    
        /* Execute the query */
        try {
            $res = $pdo->prepare($query);
            $res->execute($values);
        } catch (PDOException $e) {
            /* If there is a PDO exception, throw a standard exception */
            throw new Exception('Database query error');
        }
    
        $row = $res->fetch(PDO::FETCH_ASSOC);
    
        /* If there is a result, we must check if the password matches using password_verify() */
        if (is_array($row)) {
            if (password_verify($password, $row['account_password'])) {
                /* Authentication succeeded. Set the class properties (id and name) */
                $this->id = intval($row['account_id'], 10);
                $this->name = $name;
                $this->authenticated = true;
            
                /* Register the current Sessions on the database */
                $this->registerLoginSession();
            
                /* Finally, Return TRUE */
                return true;
            }
        }
    
        /* If we are here, it means the authentication failed: return FALSE */
        return false;
    }

    /* Saves the current Session ID with the account ID */
    public function registerLoginSession()
    {
        /* Global $pdo object */
        global $pdo;
    
        /* Check that a Session has been started */
        if (session_status() == PHP_SESSION_ACTIVE) {
            /* 	Use a REPLACE statement to:
                - insert a new row with the session id, if it doesn't exist, or...
                - update the row having the session id, if it does exist.
            */
            $query = 'REPLACE INTO myschema.account_sessions (session_id, account_id, login_time) VALUES (:sid, :accountId, NOW())';
            $values = array(':sid' => session_id(), ':accountId' => $this->id);
        
            /* Execute the query */
            try {
                $res = $pdo->prepare($query);
                $res->execute($values);
            } catch (PDOException $e) {
                /* If there is a PDO exception, throw a standard exception */
                throw new Exception('Database query error');
            }
        }
    }

    /* Login using Sessions */
    public function sessionLogin(): bool
    {
        /* Global $pdo object */
        global $pdo;
    
        /* Check that the Session has been started */
        if (session_status() == PHP_SESSION_ACTIVE) {
            /*
                Query template to look for the current session ID on the account_sessions table.
                The query also make sure the Session is not older than 7 days
            */
            $query =
        
        'SELECT * FROM myschema.account_sessions, myschema.accounts WHERE (account_sessions.session_id = :sid) ' .
        'AND (account_sessions.login_time >= (NOW() - INTERVAL 7 DAY)) AND (account_sessions.account_id = accounts.account_id) ' .
        'AND (accounts.account_enabled = 1)';
        
            /* Values array for PDO */
            $values = array(':sid' => session_id());
        
            /* Execute the query */
            try {
                $res = $pdo->prepare($query);
                $res->execute($values);
            } catch (PDOException $e) {
                /* If there is a PDO exception, throw a standard exception */
                throw new Exception('Database query error');
            }
        
            $row = $res->fetch(PDO::FETCH_ASSOC);
        
            if (is_array($row)) {
                /* Authentication succeeded. Set the class properties (id and name) and return TRUE*/
                $this->id = intval($row['account_id'], 10);
                $this->name = $row['account_name'];
                $this->authenticated = true;
            
                return true;
            }
        }
    
        /* If we are here, the authentication failed */
        return false;
    }

    /* Logout the current user */
    public function logout()
    {
        /* Global $pdo object */
        global $pdo;
    
        /* If there is no logged in user, do nothing */
        if (is_null($this->id)) {
            return;
        }
    
        /* Reset the account-related properties */
        $this->id = null;
        $this->name = null;
        $this->authenticated = false;
    
        /* If there is an open Session, remove it from the account_sessions table */
        if (session_status() == PHP_SESSION_ACTIVE) {
            /* Delete query */
            $query = 'DELETE FROM myschema.account_sessions WHERE (session_id = :sid)';
        
            /* Values array for PDO */
            $values = array(':sid' => session_id());
        
            /* Execute the query */
            try {
                $res = $pdo->prepare($query);
                $res->execute($values);
            } catch (PDOException $e) {
                /* If there is a PDO exception, throw a standard exception */
                throw new Exception('Database query error');
            }
        }
    }

    /* "Getter" function for the $authenticated variable
        Returns TRUE if the remote user is authenticated */
    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    /* Close all account Sessions except for the current one (aka: "logout from other devices") */
    public function closeOtherSessions()
    {
        /* Global $pdo object */
        global $pdo;
    
        /* If there is no logged in user, do nothing */
        if (is_null($this->id)) {
            return;
        }
    
        /* Check that a Session has been started */
        if (session_status() == PHP_SESSION_ACTIVE) {
            /* Delete all account Sessions with session_id different from the current one */
            $query = 'DELETE FROM myschema.account_sessions WHERE (session_id != :sid) AND (account_id = :account_id)';
        
            /* Values array for PDO */
            $values = array(':sid' => session_id(), ':account_id' => $this->id);
        
            /* Execute the query */
            try {
                $res = $pdo->prepare($query);
                $res->execute($values);
            } catch (PDOException $e) {
                /* If there is a PDO exception, throw a standard exception */
                throw new Exception('Database query error');
            }
        }
    }
}

?>