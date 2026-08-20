<?php
// =============================================
// AI API CONFIGURATION
// =============================================

// Puxa a chave do arquivo .env (seguro na raiz do projeto)
$api_key = getenv('OPENROUTER_API_KEY');

// ========== OPENROUTER ==========
define('OPENROUTER_API_KEY', $api_key);
define('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1/chat/completions');
define('OPENROUTER_MODEL', 'deepseek/deepseek-chat');  // Best for character
define('OPENROUTER_MAX_RETRIES', 1);
define('OPENROUTER_MAX_TOKENS', 800);
define('OPENROUTER_TEMPERATURE', 1.0);
define('OPENROUTER_TIMEOUT', 30);

// ========== DEEPSEEK (Future - Commented out) ==========
// define('DEEPSEEK_API_KEY', 'your-deepseek-key-here');
// define('DEEPSEEK_API_URL', 'https://api.deepseek.com/v1/chat/completions');
// define('DEEPSEEK_MODEL', 'deepseek-chat');
// define('DEEPSEEK_MAX_TOKENS', 700);
// define('DEEPSEEK_TEMPERATURE', 0.8);
// define('DEEPSEEK_TIMEOUT', 15);

// ========== GROQ (Backup - Free Tier) ==========
// define('GROQ_API_KEY', 'your-groq-key-here');
// define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
// define('GROQ_MODEL', 'llama3-70b-8192');
// define('GROQ_MAX_TOKENS', 700);
// define('GROQ_TEMPERATURE', 0.8);
// define('GROQ_TIMEOUT', 15);

// ========== AI PROVIDER SELECTION ==========
define('AI_PROVIDER', 'openrouter');
?>