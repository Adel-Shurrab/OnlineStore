<?php 

function lang ($phrase) {

    static $lang = array(

        'Message' => 'مرحبا',
        'Admin' => 'المدير'

    );

    return $lang[$phrase];
}
?>