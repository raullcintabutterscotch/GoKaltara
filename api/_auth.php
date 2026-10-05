<?php

function isUserLoggedIn()
{
    return isset($_SESSION["login"]) &&
        (
            $_SESSION["login"] === true ||
            $_SESSION["login"] === 1 ||
            $_SESSION["login"] === "1"
        );
}

function getUserId()
{
    return (int) ($_SESSION["id_user"] ?? 0);
}