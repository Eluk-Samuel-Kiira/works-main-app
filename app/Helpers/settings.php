<?php




if (!function_exists('getWhiteLogo')) {
    function getWhiteLogo()
    {
        // Any dynmic logo from db
        
        // Final fallback: return default logo
        return asset('assets/images/logos/Works-light.png');
    }
}

if (!function_exists('getDarkLogo')) {
    function getDarkLogo()
    {
        // Any dynmic logo from db
        
        // Final fallback: return default logo
        return asset('assets/images/logos/Works-darks.png');
    }
}


if (!function_exists('getFavicon')) {
    function getFavicon()
    {
        
        // from db or 
        // fallback
        $faviconUrl = asset('assets/images/logos/favicon.png');
        return $faviconUrl;
    }
}
