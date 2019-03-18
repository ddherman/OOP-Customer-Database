<?php

class Customer { 
    public $id;
    public $name;
    public $mobile;
    public $email;
    public $password;
    public $passwordhash;
    //public $emailConfirmed;
    private $noerrors = true;
    private $validUserInput = true;
    private $nameError = null;
    private $mobileError = null;
    private $emailError = null;
    private $passwordError = null;
    private $entryError = null;
    //private $emailConfirmationError = null;
    private $title = "Customer";
    private $tableName = "customer";
    
    function create_record() { // display "create" form
        $this->generate_html_top (1);
        $this->generate_form_group("name", $this->nameError, $this->name, "autofocus");
        $this->generate_form_group("email", $this->emailError, $this->email);
        $this->generate_form_group("mobile", $this->mobileError, $this->mobile);
        $this->generate_html_bottom (1);
    } // end function create_record()
    
    function read_record($id) { // display "read" form
        $this->select_db_record($id);
        $this->generate_html_top(2);
        $this->generate_form_group("name", $this->nameError, $this->name, "disabled");
        $this->generate_form_group("email", $this->emailError, $this->email, "disabled");
        $this->generate_form_group("mobile", $this->mobileError, $this->mobile, "disabled");
        $this->generate_html_bottom(2);
    } // end function read_record()
    
    function update_record($id) { // display "update" form
        if($this->noerrors) $this->select_db_record($id);
        $this->generate_html_top(3, $id);
        $this->generate_form_group("name", $this->nameError, $this->name, "autofocus onfocus='this.select()'");
        $this->generate_form_group("email", $this->emailError, $this->email);
        $this->generate_form_group("mobile", $this->mobileError, $this->mobile);
        $this->generate_html_bottom(3);
    } // end function update_record()
    
    function delete_record($id) { // display "read" form
        $this->select_db_record($id);
        $this->generate_html_top(4, $id);
        $this->generate_form_group("name", $this->nameError, $this->name, "disabled");
        $this->generate_form_group("email", $this->emailError, $this->email, "disabled");
        $this->generate_form_group("mobile", $this->mobileError, $this->mobile, "disabled");
        $this->generate_html_bottom(4);
    } // end function delete_record()
    
    /*
     * This method inserts one record into the table, 
     * and redirects user to List, IF user input is valid, 
     * OTHERWISE it redirects user back to Create form, with errors
     * - Input: user data from Create form
     * - Processing: INSERT (SQL)
     * - Output: None (This method does not generate HTML code,
     *   it only changes the content of the database)
     * - Precondition: Public variables set (name, email, mobile)
     *   and database connection variables are set in datase.php.
     *   Note that $id will NOT be set because the record 
     *   will be a new record so the SQL database will "auto-number"
     * - Postcondition: New record is added to the database table, 
     *   and user is redirected to the List screen (if no errors), 
     *   or Create form (if errors)
     */
    function insert_db_record () {
        if ($this->fieldsAllValid ()) { // validate user input
            // if valid data, insert record into table
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "INSERT INTO $this->tableName (name,email,mobile) values(?, ?, ?)";
            $q = $pdo->prepare($sql);
            $q->execute(array($this->name,$this->email,$this->mobile));
            Database::disconnect();
            header("Location: $this->tableName.php?fun=display_list");
        }
        else {
            // if not valid data, go back to "create" form, with errors
            // Note: error fields are set in fieldsAllValid ()method
            $this->create_record(); 
        }
    } // end function insert_db_record
    
    function select_db_record($id) {
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT * FROM $this->tableName where id = ?";
        $q = $pdo->prepare($sql);
        $q->execute(array($id));
        $data = $q->fetch(PDO::FETCH_ASSOC);
        Database::disconnect();
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->mobile = $data['mobile'];
    } // function select_db_record()
    
    function update_db_record ($id) {
        $this->id = $id;
        if ($this->fieldsAllValid()) {
            $this->noerrors = true;
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "UPDATE $this->tableName  set name = ?, email = ?, mobile = ? WHERE id = ?";
            $q = $pdo->prepare($sql);
            $q->execute(array($this->name,$this->email,$this->mobile,$this->id));
            Database::disconnect();
            header("Location: $this->tableName.php?fun=display_list");
        }
        else {
            $this->noerrors = false;
            $this->update_record($id);  // go back to "update" form
        }
    } // end function update_db_record 
    
    function delete_db_record($id) {
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "DELETE FROM $this->tableName WHERE id = ?";
        $q = $pdo->prepare($sql);
        $q->execute(array($id));
        Database::disconnect();
        header("Location: $this->tableName.php?fun=display_list");
    } // end function delete_db_record()
    
    /*
     * This method logs in the user based on the email and 
     * password hash in the customers table. If the user is not
     * found the function will return to the login screen with 
     * errors.
     * - Input: user data from Login form
     * - Processing: SELECT (SQL)
     * - Output: None (This method does not generate HTML code,
     *   it only changes the content of the database)
     * - Precondition: Public variables set (email, password, passwordhash)
     *   and database connection variables are set in datase.php.
     * - Postcondition: Entered username and password are found and the create
     *   screen is displayed (if no errors). Otherwise, the login screen will
     *   be redisplayed with the proper error message.
     */
    function login() {
        if($_POST) {
            // Set variables from the Login form
            $this->email = $_POST['email'];
            $this->password = $_POST['password'];
            $this->passwordhash = md5($this->password);

            // Check if the given user exists in the database
            $pdo = Database::connect();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "SELECT * FROM customer WHERE email = '$this->email' AND password_hash = '$this->passwordhash' LIMIT 1";
            $q = $pdo->prepare($sql);
            $q->execute(array());
            $data = $q->fetch(PDO::FETCH_ASSOC);
            Database::disconnect();

            if($data) {
                // Set the session array to the user if they exist
                $_SESSION['email'] = $this->email;
                //print_r($_SESSION['email']); exit();
                // Redirect to the table
                header("Location: $this->tableName.php?fun=display_list");
            }
            else {
                // Given user does not exist so display error message
                $this->entryError = "Invalid Email or Password";
            }
        } 
        
        // Generate the Login form
        $this->createLoginFrom();
    }// function login()
    
    /*
     * This method generates the html code for the Login 
     * form.
     */
    function createLoginFrom() {
        //Generate form
        echo "
               <!DOCTYPE html>
               <html lang='en'>
                   <head>
                       <meta charset='UTF-8'>
                       <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css' rel='stylesheet'>
                       <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js'></script>
                       <style>label {width: 5em;}</style>
                   </head>

                   <body>
                       <div class='container'>

                           <div class='span10 offset2'>

                               <div class='row'>
                                   <h1>Login</h1>
                               </div>

                               <form class='form-horizontal' action='$this->tableName.php?fun=login' method='post' enctype='multipart/form-data'>

                                   <div class='from-group'>
                                       <label class='control-label'>Email</label>
                                       <input name='email' type='text' placeholder='Email Address' value=''>
                                   </div>

                                   <div class='from-group'>
                                       <label class='control-label'>Password</label>
                                       <input id='password' name='password' type='password'  placeholder='password' value=''>
                                   </div>

                                   <div class='form-actions'>
                                       <button type='submit' class='btn btn-success'>Login</button>
                                       <a class='btn btn-info' href='$this->tableName.php?fun=signup'>Sign Up</a>
                                   </div>

                                   <div class='form-group"; echo!empty($this->entryError);  echo"? 'error' : '';'>";
                                   if (!empty($this->entryError)){
                                               echo"<span class='help-inline'>"; echo $this->entryError; echo"</span>";
                                   } echo "
                                   </div>

                               </form>

                           </div> <!-- end div: class='span10 offset1' -->

                       </div> <!-- end div: class='container' -->
                   </body>
               </html>
        ";
    }
    
    /*
     * This method adds a new user to the database with the entered
     * name, mobile number, email, and password.
     * - Input: user data from Signup form
     * - Processing: SELECT, INSERT (SQL)
     * - Output: None (This method does not generate HTML code,
     *   it only changes the content of the database)
     * - Precondition: Public variables set (name, email, mobile, password, 
     *   passwordhash), database connection variables are set in datase.php, and 
     *   enterd information passes all validation checks (ex: given email doesn't
     *   already exist).
     * - Postcondition: The entered information is added to the table, a new 
     *   user account is created that can be logged in with, and the user
     *   is redirected to the login form (if data is valid). Otherwise, the
     *   signup form is redisplayed with the proper error messages.  
     */
    function signup() {
        if(!empty($_POST)) { // if not first time through
            // Set variables from the Signup form
            $this->validUserInput = true;
            $this->name = $_POST['name'];
            $this->email = $_POST['email'];
            $this->mobile = $_POST['mobile'];
            $this->password = $_POST['password'];
            $this->passwordhash = MD5($this->password);

            // validate user input
            // Check if the name field is empty
            if(empty($this->name)) {
                $this->nameError = 'Please enter your Full Name';
                $this->validUserInput = false;
            }
            // Check if the email field is empty
            if(empty($this->email)) {
                $this->emailError = 'Please enter valid Email Address';
                $this->validUserInput = false;
            }
            // Check if the format of the entered email is valid
            else if(!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
                $this->emailError = 'Please enter a valid Email Address';
                $this->validUserInput = false;
            }
            
            // Check if the entered email already exists in the database
            $pdo = Database::connect();
            $sql = "SELECT * FROM customer";
            foreach ($pdo->query($sql) as $row) {
                if ($this->email == $row['email']) {
                    $this->emailError = 'Email has already been registered';
                    $this->validUserInput = false;
                }
            }
            Database::disconnect();
            
            // Check that the email only contains lowercase letters
            if (strcmp(strtolower($this->email),$this->email)!=0) {
                    $this->emailError = 'email address can contain only lower case letters';
                    $this->validUserInput = false;
            }
            // Check if the mobile field is empty
            if (empty($this->mobile)) {
                $this->mobileError = 'Please enter Mobile Number (or "none")';
                $this->validUserInput = false;
            }
            // Check that the entered mobile number follows the format 000-000-0000
            if (!preg_match("/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/", $this->mobile)) {
                $this->mobileError = 'Please write Mobile Number in form 000-000-0000';
                $this->validUserInput = false;
            }
            // Check if the password field is empty
            if (empty($this->password)) {
                $this->passwordError = 'Please enter valid Password';
                $this->validUserInput = false;
            }

            // Inser the new user into the database
            if ($this->validUserInput) {
                $pdo = Database::connect();
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $sql = "INSERT INTO customer (name,email,mobile,password_hash) values(?, ?, ?, ?)";
                $q = $pdo->prepare($sql);
                $q->execute(array($this->name, $this->email, $this->mobile, $this->passwordhash));

                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $sql = "SELECT * FROM customer WHERE email = ? AND password_hash = ? LIMIT 1";
                $q = $pdo->prepare($sql);
                $q->execute(array($this->email, $this->passwordhash));
                $data = $q->fetch(PDO::FETCH_ASSOC);
                Database::disconnect();
                
                //$this->sendEmailConfirmation();
                // Redirect to the Login Form
                header("Location: $this->tableName.php?fun=login");
            }
        }
        
        // Generate the Signup form
        $this->createSignupForm();
    }// function signup() {
    
    /*
     * This method generates the html code for the signup 
     * form.
     */
    function createSignupForm() {
        echo "<!DOCTYPE html>
            <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css' rel='stylesheet'>
                    <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js'></script>
                    <style>label {width: 8em;}</style>
                </head>

            <body>
                <div class='container'>

                    <div class='span10 offset2'>

                            <div class='row'>
                                    <h1>Customer Sign Up</h1>
                            </div>

                            <form class='form-horizontal' action='$this->tableName.php?fun=signup' method='post' enctype='multipart/form-data'>";

                                echo "<div class='form-group "; echo!empty($this->nameError);  echo"? 'error' : '';'>";
                                        echo "<label class='control-label'>Full Name</label>
                                        <input name='name' type='text' placeholder='Full Name' value='"; echo !empty($this->name)?$this->name:''; echo "'>
                                        "; if (!empty($this->nameError)){
                                                echo "<span class='help-inline'>" . $this->nameError . "</span>";
                                           } echo "
                                </div>";
                                           
                                echo "<div class='form-group "; echo!empty($this->mobileError);  echo"? 'error' : '';'>";
                                        echo "<label class='control-label'>Mobile Number</label>
                                        <input name='mobile' type='text' placeholder='Mobile Phone Number' value='"; echo !empty($this->mobile)?$this->mobile:''; echo "'>
                                        "; if (!empty($this->mobileError)){
                                                echo "<span class='help-inline'>" . $this->mobileError . "</span>";
                                           } echo "
                                </div>";

                                echo "<div class='form-group "; echo !empty($this->emailError);  echo"? 'error' : '';'>";
                                        echo "<label class='control-label'>Email</label>
                                        <input name='email' type='text' placeholder='Email Address' value='"; echo !empty($this->email)?$this->email:''; echo "'>
                                        "; if (!empty($this->emailError)){
                                                echo "<span class='help-inline'>" . $this->emailError . "</span>";
                                           } echo "
                                </div>";

                                echo "<div class='form-group "; echo!empty($this->passwordError);  echo"? 'error' : '';'>";
                                        echo "<label class='control-label'>Password</label>
                                        <input name='password' type='password' placeholder='Password' value='"; echo !empty($this->password)?$this->password:''; echo "'>
                                        "; if (!empty($this->passwordError)){
                                                echo "<span class='help-inline'>" . $this->passwordError . "</span>";
                                           } echo "
                                </div>";

                                echo "<div class='form-actions'>
                                    <button type='submit' class='btn btn-success'>Sign Up</button>
                                    <a class='btn btn-secondary' href='$this->tableName.php?fun=login'>Back</a>
                                </div>
                        </form>

                    </div>

                </div>
            </body>
            </html>";
    }
    
    /*function sendEmailConfirmation() {
        ini_set('SMTP', 'smtp.gmail.com');
        ini_set('smtp_port', '587');
        
        $to      = $this->email;
        $subject = 'prog03 Email Confirmation';
        $message = 'Before you can login you must first confirm your email.\n'
                    . '\n' 
                    . 'Click here to confirm your email:\n'
                    . 'http://localhost/prog03/customer.php?=confirmation';
        
        $status = mail($to, $subject, $message);
        
       echo $status; exit();
    }*/
    
    /*
     * This method logs out the current user by destroying the current session.
     * - Input: none
     * - Processing: none
     * - Output: none
     * - Precondition: A user has logged in the $_SESSION array has be set.
     * - Postcondition: The current users session will be destroyed and the 
     *   $_SESSION array cleared, sending the user back to the Login form.  
     */
    function logout() {
        session_unset();
        session_destroy();
        header("Location: $this->tableName.php");
    }
    
    /*
     * This funtion dispalys the html for the title and sets the $fun variable
     * for the given form. Allows customer.php to navigate to the proper form.
     */
    private function generate_html_top ($fun, $id=null) {
        switch ($fun) {
            case 1: // create
                $funWord = "Create"; $funNext = "insert_db_record"; 
                break;
            case 2: // read
                $funWord = "Read"; $funNext = "none"; 
                break;
            case 3: // update
                $funWord = "Update"; $funNext = "update_db_record&id=" . $id; 
                break;
            case 4: // delete
                $funWord = "Delete"; $funNext = "delete_db_record&id=" . $id; 
                break;
            default: 
                echo "Error: Invalid function: generate_html_top()"; 
                exit();
                break;
        }
        echo "<!DOCTYPE html>
        <html>
            <head>
                <title>$funWord a $this->title</title>
                    ";
        echo "
                <meta charset='UTF-8'>
                <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css' rel='stylesheet'>
                <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js'></script>
                <style>label {width: 5em;}</style>
                    "; 
        echo "
            </head>";
        echo "
            <body>
                <div class='container'>
                    <div class='span10 offset1'>
                        <p class='row'>
                            <h3>$funWord a $this->title</h3>
                        </p>
                        <form class='form-horizontal' action='$this->tableName.php?fun=$funNext' method='post'>                        
                    ";
    } // end function generate_html_top()
    
    /*
     * This fcuntion generates the html code for the buttons on the given form.
     */
    private function generate_html_bottom ($fun) {
        switch ($fun) {
            case 1: // create
                $funButton = "<button type='submit' class='btn btn-success'>Create</button>"; 
                break;
            case 2: // read
                $funButton = "";
                break;
            case 3: // update
                $funButton = "<button type='submit' class='btn btn-warning'>Update</button>";
                break;
            case 4: // delete
                $funButton = "<button type='submit' class='btn btn-danger'>Delete</button>"; 
                break;
            default: 
                echo "Error: Invalid function: generate_html_bottom()"; 
                exit();
                break;
        }
        echo " 
                            <div class='form-actions'>
                                $funButton
                                <a class='btn btn-secondary' href='$this->tableName.php?fun=display_list'>Back</a>
                            </div>
                        </form>
                    </div>

                </div> <!-- /container -->
            </body>
        </html>
                    ";
    } // end function generate_html_bottom()
    
    /*
     * This function generates the required html form groups for the given form
     * (Labels, Inputs, Errors).
     */
    private function generate_form_group ($label, $labelError, $val, $modifier="") {
        echo "<div class='form-group'";
        echo !empty($labelError) ? ' alert alert-danger ' : '';
        echo "'>";
        echo "<label class='control-label'>$label &nbsp;</label>";
        //echo "<div class='controls'>";
        echo "<input "
            . "name='$label' "
            . "type='text' "
            . "$modifier "
            . "placeholder='$label' "
            . "value='";
        echo !empty($val) ? $val : '';
        echo "'>";
        if (!empty($labelError)) {
            echo "<span class='help-inline'>";
            echo "&nbsp;&nbsp;" . $labelError;
            echo "</span>";
        }
        //echo "</div>"; // end div: class='controls'
        echo "</div>"; // end div: class='form-group'
    } // end function generate_form_group()
    
    /*
     * This fcuntion checks if all fields (name, email, mobile) are valid.
     */
    private function fieldsAllValid () {
        $valid = true;
        // Check if the name field is empty
        if (empty($this->name)) {
            $this->nameError = 'Please enter Name';
            $valid = false;
        }
        // Check if the email field is empty
        if (empty($this->email)) {
            $this->emailError = 'Please enter Email Address';
            $valid = false;
        } 
        // Check if the format of the entered email is valid
        else if ( !filter_var($this->email,FILTER_VALIDATE_EMAIL) ) {
            $this->emailError = 'Please enter a valid email address: me@mydomain.com';
            $valid = false;
        }
        // Check if the mobile field is empty
        if (empty($this->mobile)) {
            $this->mobileError = 'Please enter Mobile phone number';
            $valid = false;
        }
        return $valid;
    } // end function fieldsAllValid() 
    
    /*
     * This fucntion displays the table.
     */
    function list_records() {
        echo "<!DOCTYPE html>
        <html>
            <head>
                <title>$this->title" . "s" . "</title>
                    ";
        echo "
                <meta charset='UTF-8'>
                <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css' rel='stylesheet'>
                <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js'></script>
                    ";  
        echo "
            </head>
            <body>
                <a href='https://github.com/ddherman/OOP-Customer-Database'>Github</a><br/>
                <a href='http://csis.svsu.edu/~ddherman/prog03/uml02.png'>prog02 UML Diagram</a><br/>
                <a href='http://csis.svsu.edu/~ddherman/prog03/screenflow02.png'>prog02 Screen Flow Diagram</a><br/>
                <a href='http://csis.svsu.edu/~ddherman/prog03/uml03.png'>prog03 UML Diagram</a><br/>
                <a href='http://csis.svsu.edu/~ddherman/prog03/screenflow03.png'>prog03 Screen Flow Diagram</a><br/>
                <div class='container'>
                    <p class='row'>
                        <h3>$this->title" . "s" . "</h3>
                    </p>
                    <p>
                        <a href='$this->tableName.php?fun=display_create_form' class='btn btn-success'>Create</a>
                        <a href='$this->tableName.php?fun=logout' class='btn btn-secondary'>Log Out</a>
                    </p>
                    <div class='row'>
                        <table class='table table-striped table-bordered'>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                    ";
        $pdo = Database::connect();
        $sql = "SELECT * FROM $this->tableName ORDER BY id DESC";
        foreach ($pdo->query($sql) as $row) {
            echo "<tr>";
            echo "<td>". $row["name"] . "</td>";
            echo "<td>". $row["email"] . "</td>";
            echo "<td>". $row["mobile"] . "</td>";
            echo "<td width=250>";
            echo "<a class='btn btn-info' href='$this->tableName.php?fun=display_read_form&id=".$row["id"]."'>Read</a>";
            echo "&nbsp;";
            echo "<a class='btn btn-warning' href='$this->tableName.php?fun=display_update_form&id=".$row["id"]."'>Update</a>";
            echo "&nbsp;";
            echo "<a class='btn btn-danger' href='$this->tableName.php?fun=display_delete_form&id=".$row["id"]."'>Delete</a>";
            echo "</td>";
            echo "</tr>";
        }
        Database::disconnect();        
        echo "
                            </tbody>
                        </table>
                    </div>
                </div>

            </body>

        </html>
                    ";  
    } // end function list_records()
    
} // end class Customer