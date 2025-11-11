<?php
header('Content-Type: application/json');

$API_URL = 'http://igalfer.com/apiv2.php';
$API_KEY = '5191294a-0b640285-1030eafe-94dad40c';  // ingresa tu APIKEY ->  https://latinleads.org/#/integration

function generateOrderId() {
    return time() . rand(100, 999);
}

function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 13;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $goods_id = $_POST['goods_id'] ?? '17';   // Ingresa el ID de la oferta ->  https://latinleads.org/#/offers
    $publisherId = $_POST['publisherId'] ?? 'web_landing';
    
    if (empty($name) || empty($phone)) {
        echo json_encode(['error' => 'Nombre y teléfono son requeridos']);
        exit;
    }
    
    if (!validatePhone($phone)) {
        echo json_encode(['error' => 'Número de teléfono inválido']);
        exit;
    }
    
    $order_id = generateOrderId();
    
    $params = [
        'order_id' => $order_id,
        'name' => $name,
        'phone' => $phone,
        'api_key' => $API_KEY,
        'goods_id' => $goods_id,
        'publisherId' => $publisherId
    ];
    
    $url = $API_URL . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo json_encode(['error' => 'Error de conexión: ' . curl_error($ch)]);
        curl_close($ch);
        exit;
    }
    
    curl_close($ch);
    
    if ($httpCode === 200) {
        $apiResponse = json_decode($response, true);
        
        if ($apiResponse && $apiResponse['status'] === 'ok') {
            if ($apiResponse['is_valid'] === '1') {
                header('Location: gracias.html?order=' . $apiResponse['ext_id']);
                exit;
            } else {
                $errorMsg = 'No se pudo procesar tu solicitud. ';
                if ($apiResponse['is_wrongtelephone'] === '1') {
                    $errorMsg .= 'Número de teléfono inválido.';
                } elseif ($apiResponse['is_duplicate'] === '1') {
                    $errorMsg .= 'Este número ya fue registrado.';
                } elseif ($apiResponse['is_blacklist'] === '1') {
                    $errorMsg .= 'Número no disponible.';
                }
                echo json_encode(['error' => $errorMsg]);
            }
        } else {
            echo json_encode(['error' => 'Error del servidor: ' . ($apiResponse['error'] ?? 'Error desconocido')]);
        }
    } else {
        echo json_encode(['error' => 'Error de comunicación con el servidor']);
    }
} else {
    echo json_encode(['error' => 'Método no permitido']);
}

?>