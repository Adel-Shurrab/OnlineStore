<?php

function lang($phrase)
{

    static $lang = array(

        // navbar links
        'Home'          => 'Home',
        'CATEGORIES'    => 'Categories',
        'ITEMS'         => 'Items',
        'MEMBERS'       => 'Members',
        'ORDERS'        => 'Orders',
        'COMMENTS'      => 'Comments',
        'PromoCodes'    => 'Promo Codes',
        'LOGS'          => 'Logs',
        //dropdown links in navbar
        'EDIT_PROFILE'  => 'Edit Profile',
        'SETTINGS'      => 'Account Settings',
        'LOGOUT'        => 'Sign Out',

    );

    return $lang[$phrase];
}
