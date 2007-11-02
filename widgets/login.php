<?php
    $action = $_GET['action'];
    $user = $_POST['username'];
    $password = $_POST['password'];
    if ($action=="login")
    {
        $usersDb = new Database("drone_users");
        $users = $usersDb->getData();
        foreach ($users as $item)
        {
            if (strtolower($item['username'])==strtolower($user))
            {
                if ($item['password']==md5($password))
                {
                    session_start();
                    $_SESSION['drone_user'] = $item['username'];
                    return True;
                }
            }
        }
        return "Incorect username/password";

    }
    else
    if ($action=="logout")
    {
        session_start();
        unset($_SESSION['drone_user']);
        return True;
    }
    else
    if (isset($_SESSION['drone_user']))
    {
        session_start();
        return "user:".$_SESSION['drone_user'];
    }
    else
        return False
?>

