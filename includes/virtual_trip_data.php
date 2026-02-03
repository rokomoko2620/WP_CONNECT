<?php
// 仮想旅のシナリオデータ
// 各ステージ5問、そこから2問ずつ出題して合計10問

$virtualTripScenarios = [
    // ステージ1: 旅の始まり
    'stage1' => [
        'title' => '旅の始まり',
        'icon' => '🌅',
        'questions' => [
            1 => [
                'question' => '出発は8時！あなたはどうする？',
                'answers' => [
                    'A' => '15分前には集合したい',
                    'B' => '時間ぴったりを狙う',
                    'C' => '多少遅れても連絡すればOK'
                ]
            ],
            2 => [
                'question' => '出発直前、忘れ物に気づいた！',
                'answers' => [
                    'A' => '一旦戻って取りに行く',
                    'B' => '現地で調達する',
                    'C' => 'なくても何とかなる'
                ]
            ],
            3 => [
                'question' => '旅行の服装選び、あなたは？',
                'answers' => [
                    'A' => '機能性・動きやすさ重視',
                    'B' => '写真映えするおしゃれ重視',
                    'C' => 'その日の気分で決める'
                ]
            ],
            4 => [
                'question' => '事前リサーチはどのくらいする？',
                'answers' => [
                    'A' => '周辺のお店まで詳しく調べる',
                    'B' => '主要スポットだけ軽く調べる',
                    'C' => 'ほぼ調べず気ままに行く'
                ]
            ],
            5 => [
                'question' => '旅行の予算について',
                'answers' => [
                    'A' => '上限を決めておく',
                    'B' => '相手に合わせる',
                    'C' => 'あまり気にしない'
                ]
            ]
        ]
    ],
    
    // ステージ2: 移動中
    'stage2' => [
        'title' => '移動中',
        'icon' => '🚃',
        'questions' => [
            6 => [
                'question' => '移動中の過ごし方は？',
                'answers' => [
                    'A' => '次の予定を確認する',
                    'B' => '他愛のない話をする',
                    'C' => 'それぞれ好きなことをする'
                ]
            ],
            7 => [
                'question' => '移動中に会話が途切れて沈黙...',
                'answers' => [
                    'A' => '気にならない',
                    'B' => '少し気になる',
                    'C' => 'かなり気になる'
                ]
            ],
            8 => [
                'question' => '電車が事故で遅延！どうする？',
                'answers' => [
                    'A' => '代替ルートを探す',
                    'B' => 'タクシーに切り替える',
                    'C' => 'そのまま次の電車を待つ'
                ]
            ],
            9 => [
                'question' => '相手と体力差があるみたい...',
                'answers' => [
                    'A' => 'カフェで休憩を提案する',
                    'B' => 'コンビニで軽食を買う',
                    'C' => '予定を優先して進む'
                ]
            ],
            10 => [
                'question' => '相手が重い荷物で辛そう...',
                'answers' => [
                    'A' => '「持つよ」と代わる',
                    'B' => '「大丈夫？」と声をかける',
                    'C' => '見守る（自分で言うだろう）'
                ]
            ]
        ]
    ],
    
    // ステージ3: 現地での行動
    'stage3' => [
        'title' => '現地での行動',
        'icon' => '🏯',
        'questions' => [
            11 => [
                'question' => '予定になかった期間限定イベントを発見！',
                'answers' => [
                    'A' => '予定を変更して行く',
                    'B' => '当初の予定を優先する',
                    'C' => 'その場のノリで決める'
                ]
            ],
            12 => [
                'question' => 'お昼ご飯、どこで食べる？',
                'answers' => [
                    'A' => '事前に調べた有名店',
                    'B' => '近くで良さそうな店',
                    'C' => '空いている店を優先'
                ]
            ],
            13 => [
                'question' => '旅行中の写真撮影、どのくらい？',
                'answers' => [
                    'A' => 'たくさん撮りたい',
                    'B' => '記念程度に撮る',
                    'C' => '自分からはほぼ撮らない'
                ]
            ],
            14 => [
                'question' => '観光地を歩く距離について',
                'answers' => [
                    'A' => '多少歩くのは全然OK',
                    'B' => 'できれば少なめがいい',
                    'C' => 'なるべく歩きたくない'
                ]
            ],
            15 => [
                'question' => 'ホテルでの朝、何時に起きたい？',
                'answers' => [
                    'A' => '早起きして旅行を満喫',
                    'B' => '相手に合わせる',
                    'C' => '時間を決めずゆったり'
                ]
            ]
        ]
    ],
    
    // ステージ4: トラブル発生
    'stage4' => [
        'title' => 'トラブル発生',
        'icon' => '⚡',
        'questions' => [
            16 => [
                'question' => '突然の雨！傘がない...',
                'answers' => [
                    'A' => '室内の施設に避難する',
                    'B' => '濡れながらも続行する',
                    'C' => 'コンビニで傘を買う'
                ]
            ],
            17 => [
                'question' => '予定より時間が余ってしまった',
                'answers' => [
                    'A' => 'その辺を散歩してみる',
                    'B' => '新しいお店を探す',
                    'C' => 'ホテルに戻ってゆっくり'
                ]
            ],
            18 => [
                'question' => 'トラブルの原因が相手にあった時...',
                'answers' => [
                    'A' => 'フォローに回る',
                    'B' => '優しく指摘する',
                    'C' => 'あまり気にしない'
                ]
            ],
            19 => [
                'question' => '旅行中に不満が出てきた時は？',
                'answers' => [
                    'A' => 'その場で正直に話す',
                    'B' => '旅行後に話す',
                    'C' => '言わずにそのまま'
                ]
            ],
            20 => [
                'question' => '人気アトラクションで長い行列！',
                'answers' => [
                    'A' => '頑張って並ぶ',
                    'B' => '別のアトラクションへ',
                    'C' => 'お金を払ってパスを買う'
                ]
            ]
        ]
    ],
    
    // ステージ5: 旅の終わり
    'stage5' => [
        'title' => '旅の終わり',
        'icon' => '🌇',
        'questions' => [
            21 => [
                'question' => 'お土産選び、どうする？',
                'answers' => [
                    'A' => '時間をかけてじっくり選ぶ',
                    'B' => '気に入ったら買う程度',
                    'C' => '特に買わなくていい'
                ]
            ],
            22 => [
                'question' => '旅行のSNS投稿は？',
                'answers' => [
                    'A' => '旅行中にすぐ投稿',
                    'B' => '帰ってから投稿',
                    'C' => '特に投稿しない'
                ]
            ],
            23 => [
                'question' => '10時チェックアウト、帰りの支度は？',
                'answers' => [
                    'A' => '前日の夜に済ませる',
                    'B' => '早起きして当日にやる',
                    'C' => '起きてから考える'
                ]
            ],
            24 => [
                'question' => '旅行終了、解散前にどうする？',
                'answers' => [
                    'A' => 'カフェでゆっくり振り返る',
                    'B' => '軽く感想を話して解散',
                    'C' => 'サクッと解散'
                ]
            ],
            25 => [
                'question' => '旅行の支払い方法は？',
                'answers' => [
                    'A' => 'その都度きっちり割り勘',
                    'B' => '最後にまとめて清算',
                    'C' => '細かいことは気にしない'
                ]
            ]
        ]
    ]
];

// ステージごとに2問ずつランダムに選ぶ関数
function selectQuestions($scenarios, $seed = null) {
    if ($seed !== null) {
        mt_srand($seed);
    }
    
    $selectedQuestions = [];
    
    foreach ($scenarios as $stageKey => $stage) {
        $questionIds = array_keys($stage['questions']);
        shuffle($questionIds);
        $selected = array_slice($questionIds, 0, 2);
        
        foreach ($selected as $qId) {
            $selectedQuestions[$qId] = [
                'stage' => $stage['title'],
                'icon' => $stage['icon'],
                'question' => $stage['questions'][$qId]['question'],
                'answers' => $stage['questions'][$qId]['answers']
            ];
        }
    }
    
    // 質問IDでソート
    ksort($selectedQuestions);
    
    return $selectedQuestions;
}

// 一致度を計算する関数
function calculateMatchRate($answers1, $answers2) {
    $total = count($answers1);
    $matches = 0;
    
    foreach ($answers1 as $qId => $answer1) {
        if (isset($answers2[$qId]) && $answers1[$qId] === $answers2[$qId]) {
            $matches++;
        }
    }
    
    return ($total > 0) ? round(($matches / $total) * 100) : 0;
}

// 相性コメントを生成する関数
function getMatchComment($rate) {
    if ($rate >= 90) {
        return ['title' => '運命の旅仲間！', 'message' => '価値観がほぼ一致！最高の旅ができそう✨', 'emoji' => '💫'];
    } elseif ($rate >= 70) {
        return ['title' => '相性バッチリ！', 'message' => '似た考え方で安心して旅できそう！', 'emoji' => '🎉'];
    } elseif ($rate >= 50) {
        return ['title' => 'いいバランス！', 'message' => 'お互いの違いが新しい発見になるかも', 'emoji' => '🌈'];
    } elseif ($rate >= 30) {
        return ['title' => '冒険的な組み合わせ', 'message' => '違いを楽しめれば面白い旅に！', 'emoji' => '🎭'];
    } else {
        return ['title' => '正反対タイプ？', 'message' => '事前にしっかり話し合おう！', 'emoji' => '💬'];
    }
}
