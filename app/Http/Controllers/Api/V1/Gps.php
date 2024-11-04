<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Gps extends Controller
{
    private static string $host = 'http://52.47.148.184/processor.php';
    private static string $adminEmail = 'olekamma.uche@gmail.com';
    private static string $adminPassword = 'akanebidollz4lyf';
    private static array $headers = [
        'Accept: */*',
        'User-Agent: cti',
        'Connection: keep-alive'
    ];
    
    private const DEFAULT_JSESSIONID = 'node0181avk42w3ps91488baik3bjbh2731.node0';

    public static function loginAdmin(): array
    {
        return self::login(self::$adminEmail, self::$adminPassword);
    }

    public static function login(string $email, string $password): array
    {
        $data = [
            'email' => $email,
            'password' => $password
        ];

        $postData = http_build_query($data);
        return self::curl('/api/session', 'POST', '', $postData, self::$headers);
    }

    private static function curl(string $path, string $method, string $sessionId = '', string $data = '', array $extraHeaders = [], array $queryParams = [])
    {
        session_start(); // Ensure session is started

        $_SESSION['JSESSIONID'] = self::DEFAULT_JSESSIONID;

        $ch = curl_init();
        $url = self::$host . $path . (!empty($queryParams) ? '?' . http_build_query($queryParams) : '');
        

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        $headers = array_merge($extraHeaders, [
            'Cookie: JSESSIONID=' . ($_SESSION['JSESSIONID'] ?? '')
        ]);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($data) && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return ['error' => curl_error($ch)];
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);

        if (preg_match('/Set-Cookie: JSESSIONID=([^;]+)/', substr($response, 0, $headerSize), $matches)) {
            $_SESSION['JSESSIONID'] = $matches[1];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode >= 400) {
            return ['error' => 'HTTP Error ' . $httpCode . ': ' . $body];
        }

        curl_close($ch);

        $decodedResponse = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => json_last_error_msg()];
        }

        return $decodedResponse;
    }

    public static function getSessionId(): string
    {
        return $_SESSION['JSESSIONID'] ?? '';
    }

    public static function devices(string $id = ''): array
    {
        $queryParams = $id ? ['deviceId' => $id] : [];
        return self::curl('/api/devices', 'GET', '', '', self::$headers, $queryParams);
    }

    public static function deviceAdd($name, $uniqueId, $phone, $model, $category, $attributes): array
    {
        // Default values for new devices
        $id = -1;
        $status = 'active';
        $disabled = false;
        $lastUpdate = (new \DateTime())->format(\DateTime::ISO8601);
        $positionId = null;
        $groupId = null;
        $contact = '';

        // Prepare the data array
        $data = [
            'id' => $id,
            'name' => $name,
            'uniqueId' => $uniqueId,
            'status' => $status,
            'disabled' => $disabled,
            'lastUpdate' => $lastUpdate,
            'positionId' => $positionId,
            'groupId' => $groupId,
            'phone' => $phone,
            'model' => $model,
            'contact' => $contact,
            'category' => $category,
            'attributes' => $attributes,
        ];

        // Prepare headers
        $headers = array_merge(self::$headers, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        // Call the create function with the necessary parameters
        return self::create($data, $headers);
    }

    private static function create(array $data, array $headers): array
    {
        
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => self::$host,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => json_encode($data),
        ]);

        $response = curl_exec($curl);
        if ($response === false) {
            return ['error' => curl_error($curl)];
        }
       

        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => json_last_error_msg()];
        }

        return $decodedResponse;
    }
}
