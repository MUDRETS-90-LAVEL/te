<?php

// Пример подключения к базе данных
$conn = new mysqli("localhost", "root", "", "te");

function generateUniqueBarcode()
{
    return strval(rand(10000000, 99999999)); // Генерация случайного штрих-кода
}
function sendBookingRequest($data)
{
    // Мокаем ответ API (здесь только для примера)
    $responses = [
        ['message' => 'order successfully booked'],
        ['error' => 'barcode already exists']
    ];

    return $responses[array_rand($responses)]; // Возвращаем случайный ответ
}
function sendApprovalRequest($barcode)
{
    // Мокаем ответ API
    $responses = [
        ['message' => 'order successfully aproved'],
        ['error' => 'event cancelled'],
        ['error' => 'no tickets'],
        ['error' => 'no seats'],
        ['error' => 'fan removed']
    ];

    return $responses[array_rand($responses)];
}
function addOrder($event_id, $event_date, $ticket_adult_price, $ticket_adult_quantity, $ticket_kid_price, $ticket_kid_quantity, $conn)
{
    $barcode = generateUniqueBarcode();
    $bookingData = [
        'event_id' => $event_id,
        'event_date' => $event_date,
        'ticket_adult_price' => $ticket_adult_price,
        'ticket_adult_quantity' => $ticket_adult_quantity,
        'ticket_kid_price' => $ticket_kid_price,
        'ticket_kid_quantity' => $ticket_kid_quantity,
        'barcode' => $barcode
    ];

    // Попробуем забронировать заказ
    $response = sendBookingRequest($bookingData);

    // Повторяем, пока не будет успешный ответ
    while (isset($response['error'])) {
        if ($response['error'] == 'barcode already exists') {
            $barcode = generateUniqueBarcode(); // Генерируем новый штрих-код
            $bookingData['barcode'] = $barcode;
            $response = sendBookingRequest($bookingData);
        }
    }

    // Подтверждаем бронь
    $approvalResponse = sendApprovalRequest($barcode);

    if (isset($approvalResponse['message']) && $approvalResponse['message'] == 'order successfully aproved') {
        // Подготавливаем данные для сохранения в базу данных
        $equal_price = ($ticket_adult_price * $ticket_adult_quantity) + ($ticket_kid_price * $ticket_kid_quantity);

        $stmt = $conn->prepare("INSERT INTO orders (event_id, event_date, ticket_adult_price, ticket_adult_quantity, ticket_kid_price, ticket_kid_quantity, barcode, equal_price, created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isiiisss", $event_id, $event_date, $ticket_adult_price, $ticket_adult_quantity, $ticket_kid_price, $ticket_kid_quantity, $barcode, $equal_price);

        if ($stmt->execute()) {
            return "Order successfully added with barcode: $barcode";
        } else {
            return "Failed to add order: " . $stmt->error;
        }
    } else {
        return "Failed to approve order: " . $approvalResponse['error'];
    }
}


// Пример вызова функции
$result = addOrder(1, '2021-08-21 13:00:00', 700, 2, 450, 1, $conn);
echo $result; // Вывод результата