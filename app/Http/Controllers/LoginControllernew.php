<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginControllernew extends Controller
{
    public function executeQuery(Request $request)
    {
        // Retrieve the email, password, and IP address from the login request
        $email = $request->input('email');
        $password = $request->input('password');
        $ipAddress = $request->getClientIp();

        // Build your SQL query to check if the user exists and the password is correct
        $query = "SELECT email, password, login_count FROM users WHERE email = ? AND STATUS = 1";

        // Execute the query with parameter binding
        $user = DB::selectOne($query, [$email]);

        if ($user && password_verify($password, $user->password)) {
            // Successful login, update the count column
            DB::table('users')
                ->where('email', $email)
                ->update(['login_count' => DB::raw('login_count + 1')]);

            // Log the login data to a CSV file, including the IP address
            $this->logLoginData($user->email, $user->login_count + 1, $ipAddress);

            // Return a success response or redirect to the dashboard
            return response()->json(['message' => 'Login successful', 'login_count' => $user->login_count + 1], 200);
        } else {
            // Invalid email or password
            return response()->json(['message' => 'Invalid email or password'], 401);
        }
    }

    private function logLoginData($email, $loginCount, $ipAddress)
    {
        // Set the timezone to Indian Standard Time (IST)
        date_default_timezone_set('Asia/Kolkata');

        // Get the current date and time
        $loginTime = date('Y-m-d H:i:s');

        // Define the custom directory path to save the CSV file
        $customDirectory = 'F:/wardha/wardha_frontend/public/';
        $csvFilePath = $customDirectory . 'login_data.csv';

        // Check if the directory exists, otherwise create it
        if (!is_dir($customDirectory)) {
            mkdir($customDirectory, 0755, true);
        }

        // Check if the file exists, otherwise create a new file and write headers
        if (!file_exists($csvFilePath)) {
            $file = fopen($csvFilePath, 'w');
            fputcsv($file, ['Email', 'Login Count', 'Time of Login', 'IP Address']);
            fclose($file);
        }

        // Append login data to the CSV file
        $file = fopen($csvFilePath, 'a');
        fputcsv($file, [$email, $loginCount, $loginTime, $ipAddress]);
        fclose($file);
    }
}
