<?php


header('Content-Type: application/json');


$host = 'localhost'; 
$dbname = '';
$user = '';  
$password = '';    #変更箇所 

$conn_string = "host=$host dbname=$dbname user=$user password=$password";  #psql用
$dbconn = null;

$response = ['success' => false, 'data' => null, 'message' => ''];

try {
    $dbconn = @pg_connect($conn_string);

    if (!$dbconn) {
        throw new Exception("データベースに接続できません。");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handlePostRequest($dbconn, $response);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        handleGetRequest($dbconn, $response);
    } else {
        http_response_code(405);
        $response['message'] = '許可されていないリクエストメソッドです。';
    }

} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'データベースエラー: ' . $e->getMessage();
} finally {
    if ($dbconn) {
        pg_close($dbconn);
    }
}

echo json_encode($response);


function handlePostRequest($dbconn, array &$response) {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (!isset($input['user_id']) || !isset($input['clothes_ids']) || !is_array($input['clothes_ids'])) {
        http_response_code(400);
        $response['message'] = '無効なデータです。';
        return;
    }

    $userId = $input['user_id'];
    $clothesIds = $input['clothes_ids'];
    $coordinateDate = date('Y-m-d');

    $clothesIdsJson = json_encode($clothesIds);

    $sql = "INSERT INTO daily_coordinates (user_id, coordinate_date, clothes_ids_json)   #変更箇所
            VALUES ($1, $2, $3)";

   if (!pg_prepare($dbconn, "insert_query", $sql)) {
        throw new Exception("SQLの準備に失敗しました: " . pg_last_error($dbconn));
    }

    $result = pg_execute($dbconn, "insert_query", [$userId, $coordinateDate, $clothesIdsJson]);

    if (!$result) {
        throw new Exception("SQLの実行に失敗しました: " . pg_last_error($dbconn));
    }

    $response['success'] = true;
    $response['message'] = '服装を正常に記録しました。';
    http_response_code(201);
}

function handleGetRequest($dbconn, array &$response) {
    if (!isset($_GET['user_id'])) {
        http_response_code(400);
        $response['message'] = 'user_id パラメータは必須です。';
        return;
    }

    $userId = (int)$_GET['user_id'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $sql_coords = "SELECT coordinate_date, clothes_ids_json    #変更箇所
                   FROM daily_coordinates
                   WHERE user_id = $1
                   ORDER BY coordinate_date DESC
                   LIMIT $2
                   OFFSET $3";


    if (!pg_prepare($dbconn, "select_coords", $sql_coords)) {
        throw new Exception("SQL(coords)の準備に失敗: " . pg_last_error($dbconn));
    }
    $result_coords = pg_execute($dbconn, "select_coords", [$userId, $limit, $offset]);
    if (!$result_coords) {
        throw new Exception("SQL(coords)の実行に失敗: " . pg_last_error($dbconn));
    }

    $coordinates = pg_fetch_all($result_coords, PGSQL_ASSOC);
    

    if (empty($coordinates)) {
        $response['success'] = true;
        $response['message'] = '服装の履歴はまだありません。';
        return;
    }

    $all_clothes_ids = [];
    foreach ($coordinates as $coord) {
        $ids = json_decode($coord['clothes_ids_json'], true);
        if (is_array($ids)) {
            $all_clothes_ids = array_merge($all_clothes_ids, $ids);
        }
    }
    $unique_clothes_ids = array_unique($all_clothes_ids);

    $clothes_lookup = [];
    if (!empty($unique_clothes_ids)) {
        $placeholders = implode(',', array_map(
            fn($i) => '$' . ($i + 1), 
            array_keys($unique_clothes_ids)
        ));

        $sql_clothes = "SELECT id, name, image_url, category 
                        FROM clothes 
                        WHERE id IN ($placeholders)";
        
        if (!pg_prepare($dbconn, "select_clothes", $sql_clothes)) {
            throw new Exception("SQL(clothes)の準備に失敗: " . pg_last_error($dbconn));
        }

        $result_clothes = pg_execute($dbconn, "select_clothes", array_values($unique_clothes_ids));
        
        if (!$result_clothes) {
             throw new Exception("SQL(clothes)の実行に失敗: " . pg_last_error($dbconn));
        }

        $clothes_details = pg_fetch_all($result_clothes, PGSQL_ASSOC);




        foreach ($clothes_details as $clothe) {
            $clothes_lookup[$clothe['id']] = $clothe;
        }
    }

    $formatted_data = [];
    foreach ($coordinates as $coord) {
        $coordinate_item = [
            'date' => $coord['coordinate_date'],
            'clothes' => []
        ];

        $ids = json_decode($coord['clothes_ids_json'], true);
        if (is_array($ids)) {
            foreach ($ids as $id) {
                if (isset($clothes_lookup[$id])) {
                    $coordinate_item['clothes'][] = $clothes_lookup[$id];
                }
            }
        }
        $formatted_data[] = $coordinate_item;
    }

    $response['success'] = true;
    $response['data'] = $formatted_data;
}

?>