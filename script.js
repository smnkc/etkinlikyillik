// Takvim JavaScript Fonksiyonları

// Ay/Yıl değişikliği
function changeDate() {
    const month = document.getElementById('monthSelect').value;
    const year = document.getElementById('yearSelect').value;
    window.location.href = `index.php?month=${month}&year=${year}`;
}

// Event Modal Fonksiyonları
function openEventModal(date) {
    document.getElementById('eventDate').value = date;
    document.getElementById('eventModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeEventModal() {
    document.getElementById('eventModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('eventForm').reset();
}

// Event List Modal Fonksiyonları
function openEventListModal(date) {
    const formattedDate = formatDateForDisplay(date);
    document.getElementById('eventListTitle').textContent = `${formattedDate} - Etkinlikler`;
    
    // Etkinlikleri yükle
    loadEventsForDate(date);
    
    document.getElementById('eventListModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Yeni etkinlik eklemek için tarihi sakla
    window.currentEventDate = date;
}

function closeEventListModal() {
    document.getElementById('eventListModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function openAddEventFromList() {
    closeEventListModal();
    openEventModal(window.currentEventDate);
}

function loadEventsForDate(date) {
    fetch(`api/get_event.php?date=${date}`)
        .then(response => response.json())
        .then(data => {
            const content = document.getElementById('eventListContent');
            if (data.success && data.events.length > 0) {
                content.innerHTML = data.events.map(event => `
                    <div class="event-list-item">
                        <div class="event-list-title">${event.title}</div>
                        ${event.event_time ? `<div class="event-list-time"><i class="fas fa-clock"></i> ${event.event_time}</div>` : ''}
                        ${event.description ? `<div class="event-list-description">${event.description}</div>` : ''}
                        <div class="event-list-user"><i class="fas fa-user"></i> ${event.username}</div>
                        ${event.can_delete ? `
                            <button class="event-delete-btn" onclick="deleteEventFromList(${event.id})" title="Sil">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                `).join('');
            } else {
                content.innerHTML = '<p style="text-align: center; color: #718096;">Bu tarihte etkinlik bulunmuyor.</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('eventListContent').innerHTML = '<p style="text-align: center; color: #e53e3e;">Etkinlikler yüklenirken hata oluştu.</p>';
        });
}

function deleteEventFromList(eventId) {
    if (confirm('Bu etkinliği silmek istediğinizden emin misiniz?')) {
        deleteEvent(eventId);
    }
}

function formatDateForDisplay(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('tr-TR', options);
}

// Modal dışına tıklayınca kapat
window.onclick = function(event) {
    const modal = document.getElementById('eventModal');
    if (event.target === modal) {
        closeEventModal();
    }
}

// ESC tuşu ile modal kapat
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeEventModal();
    }
});

// Event Form Submit
document.addEventListener('DOMContentLoaded', function() {
    const eventForm = document.getElementById('eventForm');
    if (eventForm) {
        eventForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/add_event.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Etkinlik başarıyla eklendi!', 'success');
                    closeEventModal();
                    // Sayfayı yenile
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message || 'Bir hata oluştu!', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Bir hata oluştu!', 'error');
            });
        });
    }
    
    // Event tıklama işlemi
    document.addEventListener('click', function(e) {
        if (e.target.closest('.event')) {
            const event = e.target.closest('.event');
            const eventId = event.dataset.eventId;
            
            // Normal tıklama - etkinlik detaylarını göster
            showEventDetails(eventId);
        }
        
        // Takvim günü tıklama işlemi
        if (e.target.closest('.calendar-day') && !e.target.closest('.add-event-btn')) {
            const calendarDay = e.target.closest('.calendar-day');
            const date = calendarDay.dataset.date;
            
            if (date && calendarDay.classList.contains('has-events')) {
                openEventListModal(date);
            } else if (date) {
                openEventModal(date);
            }
        }
    });
});

// Etkinlik silme fonksiyonu
function deleteEvent(eventId) {
    fetch('api/delete_event.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ event_id: eventId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Etkinlik başarıyla silindi!', 'success');
            // Sayfayı yenile
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'Silme işlemi başarısız!', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Bir hata oluştu!', 'error');
    });
}

// Modal'dan etkinlik silme fonksiyonu
function deleteEventFromModal(eventId) {
    if (confirm('Bu etkinliği silmek istediğinizden emin misiniz?')) {
        closeDetailModal(); // Önce modal'ı kapat
        deleteEvent(eventId);
    }
}

// Etkinlik detaylarını göster
function showEventDetails(eventId) {
    fetch(`api/get_event.php?id=${eventId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const event = data.event;
            let detailsHtml = `
                <div class="event-details">
                    <h3>${event.title}</h3>
                    <p><strong>Tarih:</strong> ${formatDate(event.event_date)}</p>
                    ${event.event_time ? `<p><strong>Saat:</strong> ${event.event_time}</p>` : ''}
                    ${event.description ? `<p><strong>Açıklama:</strong> ${event.description}</p>` : ''}
                    <p><strong>Ekleyen:</strong> ${event.username}</p>
                    ${event.can_delete ? `
                        <div class="event-actions" style="margin-top: 20px; text-align: center;">
                            <button onclick="deleteEventFromModal(${eventId})" class="delete-btn" style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                                <i class="fas fa-trash"></i> Etkinliği Sil
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
            
            showModal('Etkinlik Detayları', detailsHtml);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Etkinlik detayları alınamadı!', 'error');
    });
}

// Genel modal gösterme fonksiyonu
function showModal(title, content) {
    // Mevcut modal varsa kaldır
    const existingModal = document.getElementById('detailModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = 'detailModal';
    modal.className = 'modal';
    modal.style.display = 'block';
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>${title}</h3>
                <span class="close" onclick="closeDetailModal()">&times;</span>
            </div>
            <div style="padding: 20px;">
                ${content}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
}

// Detay modalını kapat
function closeDetailModal() {
    const modal = document.getElementById('detailModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

// Bildirim gösterme fonksiyonu
function showNotification(message, type = 'info') {
    // Mevcut bildirimleri kaldır
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
        </div>
    `;
    
    // Notification CSS'i ekle (eğer yoksa)
    if (!document.getElementById('notificationStyles')) {
        const style = document.createElement('style');
        style.id = 'notificationStyles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                min-width: 300px;
                max-width: 500px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                animation: slideInRight 0.3s ease;
            }
            
            .notification-success {
                background: #48bb78;
                color: white;
            }
            
            .notification-error {
                background: #e53e3e;
                color: white;
            }
            
            .notification-info {
                background: #4299e1;
                color: white;
            }
            
            .notification-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 15px;
            }
            
            .notification-message {
                flex: 1;
                margin-right: 10px;
            }
            
            .notification-close {
                background: none;
                border: none;
                color: inherit;
                font-size: 18px;
                cursor: pointer;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: background-color 0.2s;
            }
            
            .notification-close:hover {
                background-color: rgba(255, 255, 255, 0.2);
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // 5 saniye sonra otomatik kaldır
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Tarih formatlama fonksiyonu
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Touch events for mobile
let touchStartY = 0;
let touchEndY = 0;

document.addEventListener('touchstart', function(e) {
    touchStartY = e.changedTouches[0].screenY;
});

document.addEventListener('touchend', function(e) {
    touchEndY = e.changedTouches[0].screenY;
    handleSwipe();
});

function handleSwipe() {
    const swipeThreshold = 50;
    const diff = touchStartY - touchEndY;
    
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            // Yukarı kaydırma - bir sonraki ay
            const nextBtn = document.querySelector('.nav-btn:last-child');
            if (nextBtn) nextBtn.click();
        } else {
            // Aşağı kaydırma - bir önceki ay
            const prevBtn = document.querySelector('.nav-btn:first-child');
            if (prevBtn) prevBtn.click();
        }
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Sadece modal açık değilken çalışsın
    if (!document.getElementById('eventModal') || document.getElementById('eventModal').style.display === 'none') {
        switch(e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                document.querySelector('.nav-btn:first-child').click();
                break;
            case 'ArrowRight':
                e.preventDefault();
                document.querySelector('.nav-btn:last-child').click();
                break;
            case 'Home':
                e.preventDefault();
                document.querySelector('.btn-today').click();
                break;
        }
    }
});

// Sayfa yüklendiğinde animasyonları başlat
document.addEventListener('DOMContentLoaded', function() {
    // Calendar günlerini sırayla animasyonla göster
    const calendarDays = document.querySelectorAll('.calendar-day');
    calendarDays.forEach((day, index) => {
        day.style.animationDelay = `${index * 0.02}s`;
    });
    
    // Bugünün tarihini vurgula
    const today = new Date();
    const todayString = today.getFullYear() + '-' + 
                       String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(today.getDate()).padStart(2, '0');
    
    const todayElement = document.querySelector(`[data-date="${todayString}"]`);
    if (todayElement) {
        todayElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    // Auto-hide notifications after 5 seconds
    const notifications = document.querySelectorAll('.notification');
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    });
});