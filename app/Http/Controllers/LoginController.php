<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function executeQuery()
    {
        // Build your SQL query
        $query = "SELECT email, password FROM users WHERE STATUS =1";

        // Execute the query
        $results = DB::select($query);

        // Return the results
        return $results;
    }
}
