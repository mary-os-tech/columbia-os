<?php
session_start();
include(__DIR__ . '/../includes/config.php');
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VS Code - Columbia OS</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            margin: 0;
            padding: 20px;
            background-color: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 16px;
            line-height: 1.5;
            overflow-x: hidden;
        }
        
        /* Top Bar mimicking VS Code */
        .top-bar {
            position: fixed;
            top: 0; left: 0; width: 100%;
            background-color: #333333;
            color: #cccccc;
            padding: 5px 15px;
            font-size: 12px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: space-between;
            z-index: 100;
            border-bottom: 1px solid #252526;
        }

        #editor {
            margin-top: 30px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .cursor {
            display: inline-block;
            width: 8px;
            height: 18px;
            background-color: #d4d4d4;
            animation: blink 1s step-end infinite;
            vertical-align: text-bottom;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }

        /* Syntax Highlighting Colors */
        .keyword { color: #569cd6; }
        .string { color: #ce9178; }
        .comment { color: #6a9955; }
        .function { color: #dcdcaa; }
    </style>
</head>
<body>

<div class="top-bar">
    <span>freelance_project.cpp - Visual Studio Code</span>
    <span id="progress-tracker">Keystrokes: 0 / 100</span>
</div>

<div id="editor"></div><span class="cursor"></span>

<script>
// The code that will be "typed" out
const sourceCode = `// Columbia OS Freelance Module
#include <iostream>
#include <vector>
#include <string>
#include <thread>

using namespace std;

class NeuralNetwork {
private:
    vector<int> layers;
    double learningRate;

public:
    NeuralNetwork(vector<int> l, double lr) {
        layers = l;
        learningRate = lr;
        initializeWeights();
    }

    void initializeWeights() {
        cout << "Initializing synaptic weights..." << endl;
        for(int i = 0; i < layers.size(); i++) {
            for(int j = 0; j < layers[i]; j++) {
                double weight = (rand() % 100) / 100.0;
            }
        }
    }

    void train(vector<vector<double>> data, int epochs) {
        for(int e = 0; e < epochs; e++) {
            cout << "Epoch " << e << " completed. Loss: 0.0" << e << endl;
        }
    }
};

int main() {
    cout << "Starting Columbia OS Backend Server..." << endl;
    vector<int> architecture = {128, 64, 32, 10};
    NeuralNetwork nn(architecture, 0.01);
    nn.train({}, 1000);
    return 0;
}`;

let currentIndex = 0;
let keystrokes = 0;
const targetKeystrokes = 100;
let isFinished = false;

function applySyntaxHighlighting(text) {
    let html = text.replace(/</g, "&lt;").replace(/>/g, "&gt;");
    html = html.replace(/\b(int|double|void|class|public|private|#include|using|namespace|return|for|if)\b/g, '<span class="keyword">$1</span>');
    html = html.replace(/".*?"/g, '<span class="string">$&</span>');
    html = html.replace(/\/\/.*$/gm, '<span class="comment">$&</span>');
    html = html.replace(/\b([a-zA-Z_]\w*)\s*(?=\()/g, '<span class="function">$1</span>');
    return html;
}

document.addEventListener('keydown', function(e) {
    if (isFinished) return;
    
    // Allow standard typing, ignore complex modifiers
    if (e.key.length > 1 && e.key !== 'Enter' && e.key !== 'Backspace' && e.key !== ' ') return;
    
    if (e.key === ' ') e.preventDefault(); // Prevent page scroll on spacebar

    let charsToAdd = 4;
    currentIndex += charsToAdd;
    keystrokes++;
    
    let currentText = sourceCode.substring(0, currentIndex);
    document.getElementById('editor').innerHTML = applySyntaxHighlighting(currentText);
    
    // FIX 1: Removed invalid backslash escapes. Using standard concatenation.
    document.getElementById('progress-tracker').innerText = "Keystrokes: " + keystrokes + " / " + targetKeystrokes;
    
    window.scrollTo(0, document.body.scrollHeight);

    if (keystrokes >= targetKeystrokes) {
        isFinished = true;
        processPayout();
    }
    
    if (currentIndex >= sourceCode.length) {
        currentIndex = 0;
    }
});

function processPayout() {
    $.ajax({
        // FIX: Relative path traversing up one directory to reach /actions
        url: '../actions/freelance_payout.php', 
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire({
                    title: 'Job Complete!',
                    text: 'Client approved the PR. +$' + response.payout.toFixed(2) + ' added to your Venmo.',
                    icon: 'success',
                    background: '#1e1e1e',
                    color: '#d4d4d4',
                    confirmButtonColor: '#569cd6',
                    confirmButtonText: 'Back to Timeline'
                }).then(() => {
                    window.location.href = '<?php echo BASE_URL; ?>/pages/../index.php';
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            Swal.fire('404 Error', 'Could not reach freelance_payout.php. Check your paths!', 'error');
        }
    });
}
</script>
</body>
</html>