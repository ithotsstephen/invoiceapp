<?php
function sanitize($conn, $str) {
    return htmlspecialchars(trim($conn->real_escape_string($str)));
}

function fy_label_from_date($dateStr) {
    // India FY: April 1 to March 31
    $d = new DateTime($dateStr);
    $y = (int)$d->format('Y');
    $m = (int)$d->format('n');
    if ($m >= 4) {
        $fyStart = $y % 100;
        $fyEnd   = ($y + 1) % 100;
    } else {
        $fyStart = ($y - 1) % 100;
        $fyEnd   = $y % 100;
    }
    return sprintf('%02d-%02d', $fyStart, $fyEnd);
}

function next_invoice_number($conn, $prefix, $dateStr) {
    $fy = fy_label_from_date($dateStr);
    // Track last sequence per FY
    $stmt = $conn->prepare("SELECT last_seq FROM invoice_numbers WHERE fy_label=? FOR UPDATE");
    $stmt->bind_param("s", $fy);
    $conn->begin_transaction();
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $seq = (int)$row['last_seq'] + 1;
            $upd = $conn->prepare("UPDATE invoice_numbers SET last_seq=? WHERE fy_label=?");
            $upd->bind_param("is", $seq, $fy);
            $upd->execute();
        } else {
            $seq = 1;
            $ins = $conn->prepare("INSERT INTO invoice_numbers (fy_label, last_seq) VALUES (?, ?)");
            $ins->bind_param("si", $fy, $seq);
            $ins->execute();
        }
        $conn->commit();
        return sprintf("%s/%s/%04d", $prefix, $fy, $seq);
    } else {
        $conn->rollback();
        throw new Exception("Failed generating invoice number");
    }
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function money_fmt($amount) {
    return number_format((float)$amount, 2, '.', '');
}

function flash_message() {
    if (!empty($_SESSION['flash'])) {
        echo '<div class="flash ' . $_SESSION['flash']['type'] . '">'
           . htmlspecialchars($_SESSION['flash']['msg']) . '</div>';
        unset($_SESSION['flash']);
    }
}

function set_flash($msg, $type='success') {
    $_SESSION['flash'] = ['msg'=>$msg, 'type'=>$type];
}

function confirm_dialog_js() {
    echo "<script>
        function confirmDelete(msg) {
            return confirm(msg || 'Are you sure? This action cannot be undone.');
        }
    </script>";
}

function print_invoice_css() {
    echo '<link rel="stylesheet" href="'.BASE_URL.'/assets/css/print.css" media="print">';
}

/**
 * Convert a number (integer) to words (English, supports up to billions).
 * Returns string like "Twelve Thousand Three Hundred Forty Five"
 */
function number_to_words_integer($num) {
    $num = (int)$num;
    if ($num === 0) return 'zero';

    $units = ['', 'one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen'];
    $tens = ['', '', 'twenty','thirty','forty','fifty','sixty','seventy','eighty','ninety'];
    $scales = [
        10000000 => 'crore',
        100000 => 'lakh',
        1000 => 'thousand',
        100 => 'hundred'
    ];

    $words = [];

    foreach ($scales as $scale => $scaleName) {
        if ($num >= $scale) {
            $count = intdiv($num, $scale);
            $num = $num % $scale;
            if ($scale >= 100) {
                // recursive for count
                $words[] = number_to_words_integer($count) . ' ' . $scaleName;
            } else {
                $words[] = number_to_words_integer($count) . ' ' . $scaleName;
            }
        }
    }

    if ($num >= 20) {
        $t = intdiv($num, 10);
        $u = $num % 10;
        $words[] = $tens[$t] . ($u ? ' ' . $units[$u] : '');
    } elseif ($num > 0) {
        $words[] = $units[$num];
    }

    return trim(implode(' ', $words));
}

/**
 * Return amount in words for INR (Rupees and Paise). Example:
 * "Twelve Thousand Three Hundred Forty Five Rupees and Fifty Six Paise Only"
 */
function amount_in_words($amount) {
    // Normalize
    $amt = (float) $amount;
    if ($amt < 0) return 'minus ' . amount_in_words(-$amt);

    $rupees = floor($amt);
    $paise = round(($amt - $rupees) * 100);

    $parts = [];
    if ($rupees > 0) {
        // Omit the word 'rupee(s)' per user request; only render the words
        $parts[] = ucfirst(number_to_words_integer($rupees));
    }
    if ($paise > 0) {
        $parts[] = ucfirst(number_to_words_integer($paise)) . ' paise';
    }
    if (empty($parts)) return 'Zero rupees only';

    $str = implode(' and ', $parts) . ' only';
    return $str;
}
?>
