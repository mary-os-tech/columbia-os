$(document).ready(function() {
    const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    
    if (userTimeZone === 'America/New_York' && !localStorage.getItem('ny_touchdown_triggered')) {
        $.ajax({
            url: 'actions/trigger_touchdown.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    localStorage.setItem('ny_touchdown_triggered', 'true');
                    
                    Swal.fire({
                        title: 'Touchdown 🗽',
                        text: 'Welcome to New York. Timeline synchronized.',
                        icon: 'info',
                        background: '#000',
                        color: '#e7e9ea',
                        confirmButtonColor: '#1d9bf0'
                    }).then(() => {
                        location.reload(); 
                    });
                }
            }
        });
    } 
    // Pre-departure phase: Get real-world location if not in NY
    else if (userTimeZone !== 'America/New_York') {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                
                console.log('📍 Geolocation Coords Captured:', lat, lon);
                
                // Strict AJAX call expecting JSON
                $.ajax({
                    url: 'includes/weather.php',
                    type: 'POST',
                    data: { lat: lat, lon: lon },
                    dataType: 'json',
                    success: function(response) {
                        console.log('☁️ Weather API Response:', response);
                        if (response.status === 'success' && response.weather) {
                            // Instantly update the Sidebar UI
                            $('#weather-icon').text(response.weather.icon);
                            $('#weather-temp').text(response.weather.temp);
                            $('#weather-city').text(response.weather.city);
                            
                            // Instantly update the Environmental CSS Body Class
                            const cond = response.weather.condition.toLowerCase();
                            $('body').removeClass('weather-rain weather-snow weather-fog');
                            
                            if (['rain', 'drizzle', 'thunderstorm'].includes(cond)) {
                                $('body').addClass('weather-rain');
                            } else if (cond === 'snow') {
                                $('body').addClass('weather-snow');
                            } else if (['mist', 'fog', 'haze'].includes(cond)) {
                                $('body').addClass('weather-fog');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("❌ Weather AJAX failed!");
                        console.error("Status:", status);
                        console.error("Error:", error);
                        console.error("Response Text:", xhr.responseText);
                    }
                });
            }, function(error) {
                console.warn("⚠️ Geolocation permission denied or failed.", error);
            });
        }
    }
});