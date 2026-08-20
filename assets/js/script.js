$(document).ready(function() {

    // --- DESCOBRE O CAMINHO CORRETO DAS PASTAS ---
    let inPagesFolder = window.location.pathname.includes('/pages/');
    let actionsPath = inPagesFolder ? '../actions/' : 'actions/';

    // ==========================================
    // 1. LÓGICA DO VIBE CHECK
    // ==========================================
    $('#btn-vibe-selector').click(function(e) {
        e.stopPropagation();
        $('#vibe-dropdown').toggle();
    });

    $('.vibe-option').click(function(e) {
        e.stopPropagation();
        let selectedVibe = $(this).data('vibe');
        let selectedIcon = $(this).data('icon');
        let selectedText = $(this).text().replace(selectedIcon, '').trim();

        $('#btn-vibe-selector').attr('data-vibe', selectedVibe);
        $('#vibe-icon').text(selectedIcon);
        $('#vibe-text').text(selectedText);
        
        $('#vibe-dropdown').hide();
    });

    $(document).click(function() {
        $('#vibe-dropdown').hide();
        $('#dropdown-menu').hide(); // Aproveita para fechar o menu de 3 pontos também
    });


    // ==========================================
    // 2. LÓGICA DAS ABAS (FEED E PERFIL)
    // ==========================================
    $('.aba-item').click(function() {
        let feedAlvo = $(this).data('feed');
        let abaAlvo = $(this).data('aba');

        if (feedAlvo) {
            $('.aba-item[data-feed]').removeClass('ativa');
            $(this).addClass('ativa');
            $('.feed-section').hide();
            $('#feed-' + feedAlvo).fadeIn(200);
        } 
        else if (abaAlvo) {
            $('.aba-item[data-aba]').removeClass('ativa');
            $(this).addClass('ativa');
            $('.secao-aba').hide();
            $('#aba-' + abaAlvo).show();
        }
    });


// ==========================================
    // 3. POSTAR NOVO TWEET (INDEX.PHP)
    // ==========================================
    // Expande a caixa de tweet ao focar no textarea
    $('#novo-tweet').focus(function() {
        $('.post-expanded-area').slideDown(200);
    });

    // Fecha a área expandida se clicar fora e a caixa estiver vazia
    $(document).click(function(e) {
        if (!$(e.target).closest('.post-box').length) {
            let texto = $('#novo-tweet').val();
            if (!texto || texto.trim() === "") {
                $('.post-expanded-area').slideUp(200);
                $('#btn-postar').prop('disabled', true);
            }
        }
    });

    // Habilita/Desabilita o botão Postar baseado no conteúdo
    $('#novo-tweet').on('input', function() {
        let texto = $(this).val().trim();
        if (texto.length > 0) {
            $('#btn-postar').prop('disabled', false);
        } else {
            $('#btn-postar').prop('disabled', true);
        }
    });

    $('#novo-tweet').keydown(function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault(); 
            $('#btn-postar').click(); 
        }
    });

    $('#btn-postar').click(function() {
        let texto = $('#novo-tweet').val();
        let vibeSelecionada = $('#btn-vibe-selector').attr('data-vibe'); 

        if (!texto || texto.trim() === "") return; 

        let botao = $(this);
        let textoOriginal = botao.text(); 
        botao.prop('disabled', true).text('Posting...');

        $.ajax({
            url: actionsPath + 'salvar_post.php',
            method: 'POST',
            data: { conteudo: texto, vibe: vibeSelecionada },
            success: function() { location.reload(); },
            error: function() {
                botao.prop('disabled', false).text(textoOriginal);
                alert("Error sending post.");
            }
        });
    });


    // ==========================================
    // 4. RESPONDER TWEET (TWEET.PHP)
    // ==========================================
    $('#btn-post-reply').click(function() {
        let texto = $('#reply-input-text').val();
        let urlParams = new URLSearchParams(window.location.search);
        let postId = urlParams.get('id');
        
        if(!texto || texto.trim() === '' || !postId) return;
        
        let btn = $(this);
        btn.text('...').prop('disabled', true);
        
        $.ajax({
            url: actionsPath + 'salvar_reply.php',
            method: 'POST', 
            data: { conteudo: texto, parent_id: postId },
            success: function() { location.reload(); },
            error: function() {
                btn.text('Reply').prop('disabled', false);
                alert("Error sending reply.");
            }
        });
    });

    $('#reply-input-text').keypress(function(e) { 
        if(e.which == 13) $('#btn-post-reply').click(); 
    });


    // ==========================================
    // 5. MODAL DE EDIÇÃO DE PERFIL
    // ==========================================
    $('#btn-abrir-modal').click(function() {
        $('#modal-editar').css('display', 'flex').hide().fadeIn(200);
    });

    $('#btn-fechar-modal').click(function() {
        $('#modal-editar').fadeOut(200);
    });

    $('#btn-salvar-perfil').click(function() {
        let btn = $(this);
        btn.text('Saving...').prop('disabled', true);

        let dados = {
            nome: $('#edit-nome').val(),
            username: $('#edit-username').val(),
            avatar: $('#edit-avatar').val(),
            header: $('#edit-header').val(),
            presence: $('#edit-presence').val(),
            bio: $('#edit-bio').val()
        };

        $.ajax({
            url: actionsPath + 'editar_perfil.php',
            method: 'POST',
            data: dados,
            success: function() { location.reload(); },
            error: function() {
                btn.text('Save').prop('disabled', false);
                alert("Error saving profile.");
            }
        });
    });


    // ==========================================
    // 6. MENU DE TRÊS PONTOS E NOTIFICAÇÕES (PERFIL.PHP)
    // ==========================================
    $('#btn-tres-pontos').click(function(e) { 
        e.stopPropagation(); 
        $('#dropdown-menu').toggle(); 
    });

    let notifAtiva = false;
    $('#btn-notificacao-perfil').click(function() {
        let user = $('.user-arroba').text().trim() || "@user";
        if(!notifAtiva) {
            alert("Notifications enabled for " + user);
            $(this).find('svg').css('fill', '#1d9bf0');
            notifAtiva = true;
        } else {
            alert("Notifications disabled for " + user);
            $(this).find('svg').css('fill', '#e7e9ea');
            notifAtiva = false;
        }
    });


    // ==========================================
    // 7. MÁGICA INTERATIVA: CURTIR, REPOSTAR E SALVAR 
    // ==========================================
    $(document).on('click', '.action-btn.like, .action-btn.repost, .action-btn.bookmark', function(e) {
        e.preventDefault(); 
        e.stopPropagation(); 

        let btn = $(this);
        let tweet = btn.closest('.tweet');
        let postId = tweet.data('post-id'); 
        
        if(!postId) {
            let urlParams = new URLSearchParams(window.location.search);
            postId = urlParams.get('id');
        }
        
        if(!postId) return; 

        let pathElement = btn.find('path')[0]; 
        let numSpan = btn.find('.action-num'); // Recuperamos a matemática!
        
        const paths = {
            heartOutline: "M16.697 5.5c-1.222-.06-2.679.51-3.89 2.16l-.805 1.09-.806-1.09C9.984 6.01 8.526 5.44 7.304 5.5c-1.243.07-2.349.78-2.91 1.91-.552 1.12-.633 2.78.479 4.82 1.074 1.97 3.257 4.27 7.129 6.61 3.87-2.34 6.052-4.64 7.126-6.61 1.111-2.04 1.03-3.7.477-4.82-.561-1.13-1.666-1.84-2.908-1.91zm4.187 7.69c-1.351 2.48-4.001 5.12-8.379 7.67l-.503.3-.504-.3c-4.379-2.55-7.029-5.19-8.382-7.67-1.36-2.5-1.41-4.86-.514-6.67.887-1.79 2.647-2.91 4.601-3.01 1.651-.09 3.368.56 4.798 2.01 1.429-1.45 3.146-2.1 4.796-2.01 1.954.1 3.714 1.22 4.601 3.01.896 1.81.846 4.17-.514 6.67z",
            heartSolid: "M20.884 13.19c-1.351 2.48-4.001 5.12-8.379 7.67l-.503.3-.504-.3C7.121 18.31 4.471 15.67 3.119 13.19 1.928 11.01 1.618 8.69 2.222 6.6 2.827 4.5 4.3 2.91 6.275 2.6c1.651-.26 3.368.3 4.798 1.75l.927.94.927-.94c1.43-1.45 3.146-2.01 4.796-1.75 1.975.31 3.448 1.9 4.053 3.99.604 2.09.294 4.41-.892 6.6z",
            bookmarkOutline: "M4 4.5C4 3.12 5.119 2 6.5 2h11C18.881 2 20 3.12 20 4.5v18.44l-8-5.71-8 5.71V4.5zM6.5 4c-.276 0-.5.22-.5.5v14.56l6-4.29 6 4.29V4.5c0-.28-.224-.5-.5-.5h-11z",
            bookmarkSolid: "M4 4.5C4 3.12 5.119 2 6.5 2h11C18.881 2 20 3.12 20 4.5v18.44l-8-5.71-8 5.71V4.5z"
        };

        let tipo = '';
        if (btn.hasClass('like')) tipo = 'like';
        else if (btn.hasClass('repost')) tipo = 'repost';
        else if (btn.hasClass('bookmark')) tipo = 'bookmark';

        let isAtivo = btn.hasClass('active');
        let acao = isAtivo ? 'remove' : 'add';

        if (isAtivo) {
            btn.removeClass('active');
            if(numSpan.length > 0) { let num = parseInt(numSpan.text()); if (!isNaN(num)) numSpan.text(num - 1); }
            if (tipo === 'like') pathElement.setAttribute('d', paths.heartOutline);
            if (tipo === 'bookmark') pathElement.setAttribute('d', paths.bookmarkOutline);
        } else {
            btn.addClass('active');
            if(numSpan.length > 0) { let num = parseInt(numSpan.text()) || 0; numSpan.text(num + 1); }
            if (tipo === 'like') pathElement.setAttribute('d', paths.heartSolid);
            if (tipo === 'bookmark') pathElement.setAttribute('d', paths.bookmarkSolid);
        }

     // SURGICAL CHANGE: Route 'like' and 'bookmark' to their specific endpoints
     let targetUrl = actionsPath + 'processar_interacao.php'; // Default legacy fallback
     if (tipo === 'like') targetUrl = actionsPath + 'toggle_like.php';
     if (tipo === 'bookmark') targetUrl = actionsPath + 'toggle_bookmark.php';

     $.ajax({
        url: targetUrl,
        method: 'POST',
        data: { post_id: postId, tipo: tipo, acao: acao },
        success: function(response) {
            console.log("Interaction saved: " + tipo);
            
         // Trigger Toast Notification ONLY if adding a bookmark
         if (tipo === 'bookmark' && acao === 'add') {
            let toast = $('#bookmark-toast');
            
            // Reset toast content to default state
            toast.find('span').text('Added to your Bookmarks');
            toast.find('#btn-add-folder').show();
            
            // Store the post_id securely in the modal overlay's data attribute
            $('#folder-modal-overlay').attr('data-post-id', postId);
            
            // Reset animation if clicked multiple times quickly
            toast.removeClass('toast-animate');
            void toast[0].offsetWidth; // Trigger DOM reflow
            
            toast.addClass('toast-animate');
            
            // Remove class after animation completes (4 seconds)
            setTimeout(function() {
                toast.removeClass('toast-animate');
            }, 4000);
        }
        },
        error: function() {
            console.error("Failed to process interaction.");
        }
    });
    });
// ==========================================
    // 8. MENU DE TRÊS PONTINHOS DOS TWEETS DO FEED
    // ==========================================
    $(document).on('click', '.btn-tweet-menu', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Evita que o clique abra o tweet
        
        // Fecha todos os outros menus antes de abrir este
        $('.tweet-dropdown').hide(); 
        
        // Abre apenas o menu do botão que foi clicado
        $(this).siblings('.tweet-dropdown').toggle();
    });

    // Fecha o menu se clicar em qualquer lugar fora da tela
    $(document).click(function() {
        $('.tweet-dropdown').hide();
    });
    // ==========================================
    // 9. DIRECT MESSAGES (DMS) LOGIC
    // ==========================================
    
    // Toggle DM Dropdown Menu
    $(document).on('click', '.btn-dm-menu', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('.dm-dropdown-menu').toggle();
    });

    // Toggle DM Info Panel
    $(document).on('click', '.btn-dm-info', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('.dm-info-panel').fadeToggle(200);
    });

    // Close DM menus when clicking outside
    $(document).click(function() {
        $('.dm-dropdown-menu').hide();
    });

// Send DM via AJAX using the correct endpoint and payload keys
$('#btn-send-dm').click(function() {
    let messageText = $('#dm-input-text').val().trim();
    // Fallback to URL parameter if hidden input is missing
    let receiverId = $('#dm-receiver-id').val() || new URLSearchParams(window.location.search).get('user'); 

    if (!messageText || messageText === "") return;

    let btn = $(this);
    btn.prop('disabled', true);
    
    // Target the correct chat container (supports both class and ID depending on the page)
    let chatContainer = $('.dm-chat-window').length ? $('.dm-chat-window') : $('#chat-area');

    // 1. Immediately append the user's message to the UI
    let safeText = messageText.replace(/</g, "&lt;").replace(/>/g, "&gt;");
    let newMessageHTML = `
        <div class="message-row sent" style="display: flex; justify-content: flex-end; margin-bottom: 12px;">
            <div class="message-bubble" style="background-color: #1d9bf0; color: #fff; padding: 10px 15px; border-radius: 16px 16px 4px 16px; max-width: 75%; display:flex; flex-direction:column;">
                <span style="word-wrap:break-word;">${safeText}</span>
            </div>
        </div>
    `;
    chatContainer.append(newMessageHTML);
    $('#dm-input-text').val('');
    if(chatContainer[0]) chatContainer[0].scrollTop = chatContainer[0].scrollHeight;

    // 2. Immediately append the Anxiety UI (Typing Dots)
    let typingHTML = `
        <div id="typing-indicator" class="message-row received" style="display: flex; justify-content: flex-start; margin-bottom: 12px;">
            <div class="message-bubble" style="background-color: #2f3336; padding: 10px 15px; border-radius: 16px 16px 16px 4px; max-width: 75%;">
                <div class="typing-dots"><span></span><span></span><span></span></div>
            </div>
        </div>
    `;
    chatContainer.append(typingHTML);
    if(chatContainer[0]) chatContainer[0].scrollTop = chatContainer[0].scrollHeight;

    // 3. Send User Message to DB
    $.ajax({
        url: actionsPath + 'enviar_dm.php',
        method: 'POST',
        data: {
            receiver: receiverId,
            message_text: messageText,
            imagen_url: '' 
        },
        success: function(response) {
            if(response.trim() === 'Success') {
                // 4. Trigger the Local Brain (AI Engine) while dots are bouncing
                $.ajax({
                    url: actionsPath + 'ai_engine.php',
                    method: 'POST',
                    data: { npc: receiverId },
                    success: function(aiResponse) {
                        $('#typing-indicator').remove(); // Remove dots
                        
                        // 5. Append AI Response to UI
                        let safeAiText = aiResponse.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                        let aiMessageHTML = `
                            <div class="message-row received" style="display: flex; justify-content: flex-start; margin-bottom: 12px;">
                                <div class="message-bubble" style="background-color: #2f3336; color: #e7e9ea; padding: 10px 15px; border-radius: 16px 16px 16px 4px; max-width: 75%; display:flex; flex-direction:column;">
                                    <span style="word-wrap:break-word;">${safeAiText}</span>
                                </div>
                            </div>
                        `;
                        chatContainer.append(aiMessageHTML);
                        if(chatContainer[0]) chatContainer[0].scrollTop = chatContainer[0].scrollHeight;
                    },
                    error: function() {
                        $('#typing-indicator').remove();
                        console.error("AI Engine failed to respond.");
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                    }
                });
            } else {
                $('#typing-indicator').remove();
                alert("Error saving message to database.");
                btn.prop('disabled', false);
            }
        },
        error: function() {
            $('#typing-indicator').remove();
            alert("Server error while sending message.");
            btn.prop('disabled', false);
        }
    });
});
    // Allow Enter key to send DM (without Shift)
    $('#dm-input-text').keypress(function(e) {
        if (e.which == 13 && !e.shiftKey) {
            e.preventDefault();
            $('#btn-send-dm').click();
        }
    });
    // ==========================================
    // 10. REAL-TIME DM POLLING (3 SECONDS)
    // ==========================================
    if (window.location.pathname.includes('dm.php')) {
        setInterval(function() {
            let urlParams = new URLSearchParams(window.location.search);
            let npcUser = urlParams.get('user');
            
            if (!npcUser) return;

            // Find the highest message ID currently rendered on the screen
            let lastId = 0;
            $('.message-row').each(function() {
                let msgId = parseInt($(this).attr('data-msg-id'));
                if (msgId > lastId) lastId = msgId;
            });

            $.ajax({
                url: actionsPath + 'fetch_dms.php',
                method: 'POST',
                data: { npc: npcUser, last_id: lastId },
                dataType: 'json',
                success: function(messages) {
                    if (messages.length > 0) {
                        messages.forEach(function(msg) {
                            // Prevent duplicates just in case
                            if ($('.message-row[data-msg-id="' + msg.id + '"]').length === 0) {
                                let isSent = (msg.sender !== npcUser);
                                let rowClass = isSent ? 'sent' : 'received';
                                let rowStyle = isSent ? "display: flex; justify-content: flex-end; margin-bottom: 12px;" : "display: flex; justify-content: flex-start; margin-bottom: 12px;";
                                let bubbleStyle = isSent ? "background-color: #1d9bf0; color: #fff; padding: 10px 15px; border-radius: 16px 16px 4px 16px; max-width: 75%;" : "background-color: #2f3336; color: #e7e9ea; padding: 10px 15px; border-radius: 16px 16px 16px 4px; max-width: 75%;";
                                
                                // Format time to HH:MM
                                let dateObj = new Date(msg.timestamp);
                                let timeStr = String(dateObj.getHours()).padStart(2, '0') + ':' + String(dateObj.getMinutes()).padStart(2, '0');
                                let safeText = msg.message_text.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                                
                                let html = `
                                <div class="message-row ${rowClass}" style="${rowStyle}" data-msg-id="${msg.id}">
                                    <div class="message-bubble" style="${bubbleStyle} display:flex; flex-direction:column;">
                                        <span style="word-wrap:break-word;">${safeText}</span>
                                        <span class="msg-time" style="font-size: 0.7rem; opacity: 0.7; margin-top: 4px; text-align: right;">${timeStr}</span>
                                    </div>
                                </div>`;
                                
                                $('#chat-area').append(html);
                            }
                        });
                        
                        // Auto-scroll to bottom when new messages arrive
                        let chatArea = document.getElementById('chat-area');
                        if (chatArea) chatArea.scrollTop = chatArea.scrollHeight;
                    }
                }
            });
        }, 3000);
    }
  // ==========================================
    // 11. BOOKMARK FOLDERS MODAL LOGIC
    // ==========================================
    
    // Open Modal and Fetch Folders
    $(document).on('click', '#btn-add-folder', function(e) {
        e.preventDefault();
        $('#bookmark-toast').removeClass('toast-animate'); // Hide toast
        $('#folder-modal-overlay').css('display', 'flex').hide().fadeIn(200);
        $('#new-folder-name').focus();

        // Fetch existing folders via AJAX
        $.post(actionsPath + 'manage_folders.php', { action: 'fetch' }, function(data) {
            let folders = JSON.parse(data);
            let html = '';
            if (folders.length > 0) {
                folders.forEach(f => {
                    html += `<div class="folder-item" data-id="${f.id}" style="padding: 12px; border: 1px solid #2f3336; border-radius: 8px; color: #e7e9ea; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 10px;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.backgroundColor='transparent'">
                                📁 <span style="font-weight: bold;">${f.folder_name}</span>
                             </div>`;
                });
            } else {
                html = '<p style="color: #71767b; font-size: 0.9rem; text-align: center; margin: 20px 0;">No folders yet.</p>';
            }
            $('#folder-list-placeholder').html(html);
        });
    });

   // Create New Folder & Assign
   $('#btn-create-folder').click(function() {
    let folderName = $('#new-folder-name').val().trim();
    let postId = $('#folder-modal-overlay').attr('data-post-id');
    
    if (!folderName || !postId) return;
    
    let btn = $(this);
    btn.prop('disabled', true).text('...');

    $.post(actionsPath + 'manage_folders.php', { action: 'create', folder_name: folderName, post_id: postId }, function(response) {
        $('#folder-modal-overlay').fadeOut(200);
        $('#new-folder-name').val('');
        btn.prop('disabled', false).text('Create');

        // Trigger Custom Success Toast
        let toast = $('#bookmark-toast');
        toast.find('span').text(`Added to folder '${folderName}'`);
        toast.find('#btn-add-folder').hide(); // Hide the link
        toast.removeClass('toast-animate');
        void toast[0].offsetWidth;
        toast.addClass('toast-animate');
        setTimeout(() => toast.removeClass('toast-animate'), 4000);
    });
});

// Assign to Existing Folder
$(document).on('click', '.folder-item', function() {
    let folderId = $(this).attr('data-id');
    let folderName = $(this).find('span').text(); // Extract folder name from UI
    let postId = $('#folder-modal-overlay').attr('data-post-id');
    
    if (!folderId || !postId) return;

    $.post(actionsPath + 'manage_folders.php', { action: 'assign', folder_id: folderId, post_id: postId }, function(response) {
        $('#folder-modal-overlay').fadeOut(200);

        // Trigger Custom Success Toast
        let toast = $('#bookmark-toast');
        toast.find('span').text(`Added to folder '${folderName}'`);
        toast.find('#btn-add-folder').hide(); // Hide the link
        toast.removeClass('toast-animate');
        void toast[0].offsetWidth;
        toast.addClass('toast-animate');
        setTimeout(() => toast.removeClass('toast-animate'), 4000);
    });
});

    // Close Modal Triggers
    $('#close-folder-modal').click(function() {
        $('#folder-modal-overlay').fadeOut(200);
        $('#new-folder-name').val('');
    });

    $('#folder-modal-overlay').click(function(e) {
        if (e.target === this) {
            $(this).fadeOut(200);
            $('#new-folder-name').val('');
        }
    });
});
