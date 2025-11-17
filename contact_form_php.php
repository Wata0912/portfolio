<?php
// ===== 設定 =====
// ここにメールを受信したいアドレスを入力してください
$to = "241211@st.yoshida-g.ac.jp";

// メールの件名
$subject = "ポートフォリオサイトからのお問い合わせ";

// 送信元として表示される名前
$from_name = "Portfolio Contact Form";

// ===== セキュリティ設定 =====
// POSTリクエストのみ受け付ける
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    die("Method Not Allowed");
}

// ===== データの取得とサニタイズ =====
function sanitize_input($data) {
    $data = trim($data); // 前後の空白を削除
    $data = stripslashes($data); // スラッシュを削除
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // HTMLタグをエスケープ
    return $data;
}

// フォームデータの取得
$name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
$user_subject = isset($_POST['subject']) ? sanitize_input($_POST['subject']) : '';
$message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';

// ===== バリデーション =====
$errors = [];

// 名前のチェック
if (empty($name)) {
    $errors[] = "お名前を入力してください。";
} elseif (strlen($name) > 100) {
    $errors[] = "お名前は100文字以内で入力してください。";
}

// メールアドレスのチェック
if (empty($email)) {
    $errors[] = "メールアドレスを入力してください。";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "有効なメールアドレスを入力してください。";
}

// 件名のチェック（オプショナル）
if (!empty($user_subject) && strlen($user_subject) > 200) {
    $errors[] = "件名は200文字以内で入力してください。";
}

// メッセージのチェック
if (empty($message)) {
    $errors[] = "メッセージを入力してください。";
} elseif (strlen($message) > 5000) {
    $errors[] = "メッセージは5000文字以内で入力してください。";
}

// エラーがある場合
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit;
}

// ===== メール本文の作成 =====
$email_body = "ポートフォリオサイトからお問い合わせがありました。\n\n";
$email_body .= "【お名前】\n";
$email_body .= $name . "\n\n";
$email_body .= "【メールアドレス】\n";
$email_body .= $email . "\n\n";

if (!empty($user_subject)) {
    $email_body .= "【件名】\n";
    $email_body .= $user_subject . "\n\n";
}

$email_body .= "【メッセージ】\n";
$email_body .= $message . "\n\n";
$email_body .= "---\n";
$email_body .= "送信日時: " . date('Y年m月d日 H:i:s') . "\n";
$email_body .= "送信元IP: " . $_SERVER['REMOTE_ADDR'] . "\n";

// ===== メールヘッダーの設定 =====
$headers = [];
$headers[] = "From: " . $from_name . " <noreply@" . $_SERVER['HTTP_HOST'] . ">";
$headers[] = "Reply-To: " . $email;
$headers[] = "X-Mailer: PHP/" . phpversion();
$headers[] = "Content-Type: text/plain; charset=UTF-8";

// ===== メール送信 =====
$mail_sent = mail($to, $subject, $email_body, implode("\r\n", $headers));

// ===== レスポンスの返却 =====
header('Content-Type: application/json; charset=utf-8');

if ($mail_sent) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'お問い合わせを受け付けました。ありがとうございます。'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'メール送信に失敗しました。時間をおいて再度お試しください。'
    ]);
}
?>
