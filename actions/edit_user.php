<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/conn.php");

session_start();

$errors = [];

// ------------------------
// *
// *
// *      Update USER
// *
// *
// ------------------------

if( isset($_POST["action"]) && $_POST["action"] == "update" ) :

    // echo "<pre>";
    // print_r($_POST);
    // print_r($_FILES);
    // exit;

    // print_r($_POST);
    if( isset($_SESSION["user_id"]) && ($_SESSION["user_id"] == $_POST["user_id"] || $_SESSION["role"] == 1) ) {
        $user_id =          $_POST["user_id"];
        $first_name =       $_POST["first_name"];
        $last_name =        $_POST["last_name"];
        $address =          $_POST["address"];
        $address2 =         $_POST["address2"];
        $city =             $_POST["city"];
        $postal_code =      $_POST["postal_code"];
        $email =            $_POST["email"];
        $profile_pic_id =   NULL;
        $province_id = (isset($_POST["province_id"])) ? $_POST["province_id"] : 0; // ? is if true it will return whatever is after and : is if its false it will return what comes after


        // If the profile picture is set ( uploaded a file in the edit user page ) and there is no errors with the file upload
        if( isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["error"] == 0 ) {
            if(
                (
                    $_FILES["profile_pic"]["type"] == "image/jpeg" ||
                    $_FILES["profile_pic"]["type"] == "image/jpg" ||
                    $_FILES["profile_pic"]["type"] == "image/png" ||
                    $_FILES["profile_pic"]["type"] == "image/gif"
                ) 
                && 
                $_FILES["profile_pic"]["size"] < 2000000000000
            ) {
                // if statement true - correct file type and size 


                $file_name = $_SERVER["DOCUMENT_ROOT"] . "/uploads/" . $_FILES["profile_pic"]["name"];

                //check if the file already exists
                if( !file_exists( $file_name ) ) {

                    // upload file to uploads folder
                    if( move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $file_name) ) {

                        // Insert image into database 
                        $insert_image_query = "INSERT INTO images (url, owner_id) VALUES ( '" . str_replace($_SERVER[DOCUMENT_ROOT], "", $file_name) . "', $user_id)";

                        if( mysqli_query($conn, $insert_image_query) ) {
                            $profile_pic_id = mysqli_insert_id($conn);
                        }
                            

                        
                    }


                } else {
                    $errors[] = "File already exists";
                }



            } else {
                $errors[] = "Incorrect file type or file too large, please limit .";
            } // end if for file size and type

        } // End if profile picture is set








        if( ($first_name == '' || $last_name == '') && !empty($errors) ) {
            $errors[] = "Fields can not be empty";
        } else {



            $update_query =    "UPDATE users 
                                SET first_name =    '$first_name', 
                                    last_name =     '$last_name',
                                    address =       '$address',
                                    address2 =      '$address2',
                                    city =          '$city',
                                    postal_code =   '$postal_code',
                                    email =         '$email',
                                    province_id =    $province_id";
            $update_query .= ($profile_pic_id != NULL) ? ",profile_pic_id = $profile_pic_id" : "";
            $update_query .= " WHERE id = $user_id";


        
            if ( mysqli_query($conn, $update_query) ) {

                header("Location: " . strtok( $_SERVER["HTTP_REFERER"], "?") . "?user_id=" . $user_id . "&success=User+Updated" );

            } else {
                $errors[] = mysqli_error($conn);
            }
        }
        

    } else {
        $errors[] = "You do not have permission to do that.";
    }
elseif( isset($_POST["action"]) && $_POST["action"] == "delete") :
    $user_id = $_POST["user_id"];
    $delete_query = "DELETE FROM users WHERE id = $user_id";
    $select_query = "SELECT * FROM users WHERE id = $user_id";

    if($user_result = mysqli_query($conn, $select_query)) {
        while($user_row = mysqli_fetch_array($user_result)) {
            if($user_row["role"] != 1) {

                if(mysqli_query($conn, $delete_query)) {
                    if($user_row["id"] == $_SESSION["user_id"]) {

                        session_destroy();
                        header("Location: http://" . $_SERVER["SERVER_NAME"]);

                    } else {

                        header("Location: http://" . $_SERVER["SERVER_NAME"] . "/members.php");
                    }
                    
                } else {
                     
                    $errors[] = mysqli_error($conn); 
                }

            } else {
                $errors[] = "Cannot delete super admin";
            }
        }
    }else {
        $errors[] = "User does not exist" . mysqli_errors($conn);
    }

elseif( isset($_GET["action"]) && $_POST["action"] == "change_password") :

    $user_id = $_POST["user_id"];
    $current_password = md5($_POST["password"]);
    $new_password = md5($_POST["new_password"]);
    $new_password2 = md5($_POST["new_password2"]);

    $select_query = "SELECT * FROM users WHERE id = $user_id AND password = '$current_password'";

    $select_result = mysqli_query($conn, $select_query);

    if(mysqli_num_rows($select_result) > 0){

        if($new_password == $new_password2){
            
            $update_query = "UPDATE users SET password = '$new_password' WHERE id = $user_id";
            
            if(mysqli_query($conn, $update_query)) {
                header("Location: http://" . $_SERVER["SERVER_NAME"] . "/profile.php?success=Password+Reset");
            } else {
                $errors[] = "something went wrong: " . mysqli_error($conn); 
            }

        } else {
            $errors[] = "New passwords do not match";
        }
    } else {
        $errors[] = "Current password is incorrect " . mysqli_error($conn); 
    } 

endif;

if( !empty($errors) ) { // if there was an error this will check the array for errors and send me back to the page i was on and show the error code

    $query = http_build_query( array("errors" => $errors) );

    header("Location: " . strtok($_SERVER["HTTP_REFERER"], "?") . "?user_id=" . $user_id . "&" . $query);
    
}


?>