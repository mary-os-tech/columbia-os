document.addEventListener("DOMContentLoaded", function() {
    // 1. Initialize Battery State
    let batteryLevel = localStorage.getItem('columbia_battery');
    if (batteryLevel === null) {
        batteryLevel = 100;
        localStorage.setItem('columbia_battery', batteryLevel);
    } else {
        batteryLevel = parseInt(batteryLevel);
    }

    updateBatteryUI(batteryLevel);
    checkBatteryDeath(batteryLevel);

    // 2. Drain Battery: 1% every 30 seconds
    setInterval(function() {
        if (batteryLevel > 0) {
            batteryLevel -= 1;
            localStorage.setItem('columbia_battery', batteryLevel);
            updateBatteryUI(batteryLevel);
            checkBatteryDeath(batteryLevel);
        }
    }, 30000);

    // 3. Screen Time Tracker: Ping backend every 60 seconds
    setInterval(function() {
        $.ajax({
            url: '/Columbia-os/actions/log_screen_time.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                console.log("Columbia OS: Screen time logged.");
            }
        });
    }, 60000);

   // UI Updater
   function updateBatteryUI(level) {
    const indicatorText = document.getElementById('battery-indicator-text');
    const batterySvg = document.getElementById('battery-svg');
    const batteryFill = document.getElementById('battery-fill');
    
    if (indicatorText) {
        indicatorText.innerText = level + '%';
    }
    
    if (batterySvg && batteryFill) {
        // Calculate dynamic width for the SVG inner rectangle (Max width is 12px)
        const fillWidth = (level / 100) * 12;
        batteryFill.setAttribute('width', Math.max(fillWidth, 0)); // Prevent negative width
        
        // Apply Twitter-accurate colors based on thresholds
        if (level >= 50) {
            batterySvg.style.color = '#00ba7c'; // Twitter Green
            indicatorText.style.color = '#00ba7c';
        } else if (level >= 20) {
            batterySvg.style.color = '#ffd400'; // Twitter Yellow
            indicatorText.style.color = '#ffd400';
        } else {
            batterySvg.style.color = '#f4212e'; // Twitter Red
            indicatorText.style.color = '#f4212e';
        }
    }
}

    // Death Overlay
    function checkBatteryDeath(level) {
        if (level <= 5) {
            if (!document.getElementById('battery-death-overlay')) {
                const overlay = document.createElement('div');
                overlay.id = 'battery-death-overlay';
                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100vw';
                overlay.style.height = '100vh';
                overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.98)';
                overlay.style.color = '#fff';
                overlay.style.display = 'flex';
                overlay.style.flexDirection = 'column';
                overlay.style.justifyContent = 'center';
                overlay.style.alignItems = 'center';
                overlay.style.zIndex = '999999';
                overlay.style.fontFamily = 'sans-serif';
                
                overlay.innerHTML = `
                    <i class="fa-solid fa-battery-empty" style="font-size: 4rem; color: #f4212e; margin-bottom: 20px;"></i>
                    <h2 style="margin:0;">Low Battery</h2>
                    <p style="color: #71767b;">Please plug in your device to continue.</p>
                    <button id="plug-in-btn" style="margin-top: 20px; padding: 10px 20px; background: #1d9bf0; color: white; border: none; border-radius: 20px; cursor: pointer; font-weight: bold;">Plug In (Reset to 100%)</button>
                `;
                document.body.appendChild(overlay);

                // Reset button for testing purposes
                document.getElementById('plug-in-btn').addEventListener('click', function() {
                    localStorage.setItem('columbia_battery', 100);
                    batteryLevel = 100;
                    document.body.removeChild(overlay);
                    updateBatteryUI(100);
                });
            }
        }
    }
});