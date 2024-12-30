<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MacAddressController extends Controller
{
    public function getMacAddresses()
    {
        // MAC Address of the Device
        $deviceMac = exec('getmac'); // On Linux/Mac: `ifconfig` | On Windows: `getmac`

        // MAC Address of the Router
        $routerMac = $this->getRouterMacAddress();

        // Check if User is Connected to Router
        $isConnected = $this->isUserConnectedToRouter();

        // Return JSON response
        return response()->json([
            'device_mac' => $deviceMac,
            'router_mac' => $routerMac,
            'is_connected_to_router' => $isConnected,
        ]);
    }

    private function getRouterMacAddress()
    {
        $output = [];
        exec('arp -a', $output); // Run ARP command to get router MAC
        foreach ($output as $line) {
            if (str_contains($line, '192.168.1.1')) { // Change this to your router IP
                preg_match('/([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})/', $line, $matches);
                return $matches[0] ?? 'Unknown';
            }
        }
        return 'Not Found';
    }

    private function isUserConnectedToRouter()
    {
        // Local IP Address of the Device
        $localIp = $this->getLocalIpAddress();

        // Router IP (default assumed to be 192.168.1.1, change as needed)
        $routerIp = '192.168.1.1';

        // Compare if the Local IP is in the Router's subnet
        return $this->isSameSubnet($localIp, $routerIp);
    }

    private function getLocalIpAddress()
    {
        // Get Local IP Address (Linux/Mac/Windows)
        return gethostbyname(gethostname());
    }

    private function isSameSubnet($localIp, $routerIp)
    {
        // Convert IPs to binary
        $localBinary = ip2long($localIp);
        $routerBinary = ip2long($routerIp);

        // Subnet Mask for a typical home network (255.255.255.0)
        $subnetMask = ip2long('255.255.255.0');

        // Check if IPs are in the same subnet
        return ($localBinary & $subnetMask) === ($routerBinary & $subnetMask);
    }
}
