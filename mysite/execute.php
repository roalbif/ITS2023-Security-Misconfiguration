<?php
    if (isset($_POST['code'])) {
        $code = str_replace('\'', '"', $_POST['code']); // Replace single quotes with double quotes

        ob_start(); // Start output buffering

        eval($code); // Execute the code

        $output = ob_get_clean(); // Capture the output

        echo $output; // Send the output back to the client
    }
?>