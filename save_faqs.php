<?php
$file = 'saved_faqs.json';

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (isset($data['question']) && isset($data['answer'])) {
    $newFaq = [ "question" => $data['question'], "answer" => $data['answer'] ];
    $existingFaqs = [];

    if (file_exists($file)) {
        $existingFaqs = json_decode(file_get_contents($file), true) ?? [];
    }


    foreach ($existingFaqs as $faq) {
        if ($faq['question'] === $data['question']) {
            echo json_encode(["status" => "duplicate"]);
            exit;
        }
    }

    $existingFaqs[] = $newFaq;
    file_put_contents($file, json_encode($existingFaqs, JSON_PRETTY_PRINT));
    echo json_encode(["status" => "saved"]);
} else {
    
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode(["status" => "updated"]);
}
