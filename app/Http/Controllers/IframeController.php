<?php

// app/Http/Controllers/IframeController.php

namespace App\Http\Controllers;

class IframeController extends Controller
{
    public function getIframeUrl()
    {
        // Replace this with your actual logic to retrieve the iframe URL
        $iframeUrl = "https://public.tableau.com/views/SawangiSolarDashboard/SawangiFinalDashboard?:language=en-US&publish=yes&:display_count=n&:origin=viz_share_link";

        return response()->json(['iframeSrc' => $iframeUrl]);
    }
}
