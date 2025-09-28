// Travian Real-time Game Features
// Maximum Livewire Integration with Original Travian Assets

document.addEventListener('livewire:init', () => {
  // Initialize Travian Real-time Features
  initializeTravianRealtime();

  // Connection monitoring
  initializeConnectionMonitoring();

  // Game polling system
  initializeGamePolling();

  // Event handlers
  initializeEventHandlers();

  // UI enhancements
  initializeUIEnhancements();
});

function initializeTravianRealtime() {
  console.log('🎮 Initializing Travian Real-time Features');

  // Add Travian background
  document.body.classList.add('travian-background');

  // Initialize tooltips
  initializeTooltips();

  // Initialize animations
  initializeAnimations();
}

function initializeConnectionMonitoring() {
  let connectionStatus = 'connected';
  let reconnectAttempts = 0;
  const maxReconnectAttempts = 5;

  // Monitor connection status
  window.addEventListener('online', () => {
    connectionStatus = 'connected';
    reconnectAttempts = 0;
    Livewire.dispatch('connectionStatusChanged', { status: 'connected' });
    showNotification('Connection restored', 'success');
  });

  window.addEventListener('offline', () => {
    connectionStatus = 'disconnected';
    Livewire.dispatch('connectionStatusChanged', { status: 'disconnected' });
    showNotification('Connection lost', 'error');
  });

  // Monitor Livewire connection
  Livewire.hook('morph.updated', ({ component }) => {
    if (connectionStatus === 'disconnected') {
      connectionStatus = 'connected';
      Livewire.dispatch('connectionStatusChanged', { status: 'connected' });
    }
  });

  // Auto-reconnect on errors
  Livewire.hook('request.exception', ({ component, fail, preventDefault }) => {
    if (reconnectAttempts < maxReconnectAttempts) {
      reconnectAttempts++;
      setTimeout(() => {
        Livewire.dispatch('refreshGameData');
      }, 2000 * reconnectAttempts);
    }
  });
}

function initializeGamePolling() {
  let pollingInterval = null;
  let isPolling = false;

  // Start game polling
  Livewire.on('start-game-polling', (event) => {
    if (pollingInterval) {
      clearInterval(pollingInterval);
    }

    const interval = event.interval || 5000;
    isPolling = true;

    pollingInterval = setInterval(() => {
      if (isPolling) {
        Livewire.dispatch('processGameTick');
      }
    }, interval);

    console.log(`🔄 Game polling started (${interval}ms interval)`);
  });

  // Stop game polling
  Livewire.on('stop-game-polling', () => {
    if (pollingInterval) {
      clearInterval(pollingInterval);
      pollingInterval = null;
    }
    isPolling = false;
    console.log('⏹️ Game polling stopped');
  });

  // Initialize village polling
  Livewire.on('initialize-village-polling', (event) => {
    const interval = event.interval || 10000;
    setInterval(() => {
      Livewire.dispatch('processVillageTick');
    }, interval);
  });
}

function initializeEventHandlers() {
  // Game tick events
  Livewire.on('gameTickProcessed', () => {
    console.log('✅ Game tick processed');
    updateLastUpdateTime();
  });

  Livewire.on('gameTickError', (event) => {
    console.error('❌ Game tick error:', event.message);
    showNotification('Game tick error: ' + event.message, 'error');
  });

  // Building events
  Livewire.on('buildingCompleted', (event) => {
    showNotification('Building completed: ' + event.name, 'success');
    playSound('building-complete');
    showBuildingAnimation(event.buildingId);
  });

  Livewire.on('buildingProgressUpdated', (event) => {
    updateBuildingProgress(event.buildingId, event.progress);
  });

  // Training events
  Livewire.on('trainingCompleted', (event) => {
    showNotification('Training completed: ' + event.unitName, 'success');
    playSound('training-complete');
    showTrainingAnimation(event.unitId);
  });

  Livewire.on('trainingProgressUpdated', (event) => {
    updateTrainingProgress(event.unitId, event.progress);
  });

  // Resource events
  Livewire.on('resourceUpdated', (event) => {
    updateResourceDisplay(event.resource);
    if (event.isFull) {
      showNotification('Storage full for ' + event.resource, 'warning');
    }
  });

  Livewire.on('resourceProductionUpdated', (event) => {
    updateResourceProduction(event.production);
  });

  // Village events
  Livewire.on('villageUpdated', (event) => {
    updateVillageDisplay(event.village);
  });

  Livewire.on('villageEventOccurred', (event) => {
    showNotification(event.message, event.type);
    if (event.sound) {
      playSound(event.sound);
    }
  });

  // Battle events
  Livewire.on('battleReportReceived', (event) => {
    showNotification('New battle report received', 'warning');
    playSound('battle-report');
    showBattleReportModal(event.report);
  });

  Livewire.on('battle-report-notification', (event) => {
    showBattleReportNotification(event);
  });

  // Market events
  Livewire.on('marketOfferUpdated', (event) => {
    updateMarketDisplay(event.offer);
  });

  // Diplomatic events
  Livewire.on('diplomaticEventOccurred', (event) => {
    showNotification('Diplomatic event: ' + event.event, 'info');
    playSound('diplomatic-event');
  });

  // Achievement events
  Livewire.on('achievementUnlocked', (event) => {
    showAchievementModal(event.achievement);
    playSound('achievement');
  });

  Livewire.on('achievement-notification', (event) => {
    showAchievementNotification(event);
  });

  // Alliance events
  Livewire.on('allianceUpdated', (event) => {
    updateAllianceDisplay(event.alliance);
  });
}

function initializeUIEnhancements() {
  // Add Travian-specific UI enhancements
  addTravianTooltips();
  addTravianAnimations();
  addTravianSounds();
  addTravianNotifications();
}

function addTravianTooltips() {
  // Add tooltips to all Travian elements
  document.querySelectorAll('[data-travian-tooltip]').forEach((element) => {
    element.addEventListener('mouseenter', (e) => {
      showTooltip(e.target, e.target.dataset.travianTooltip);
    });

    element.addEventListener('mouseleave', () => {
      hideTooltip();
    });
  });
}

function addTravianAnimations() {
  // Add entrance animations
  document.querySelectorAll('.travian-fade-in').forEach((element) => {
    element.style.opacity = '0';
    element.style.transform = 'translateY(20px)';

    setTimeout(() => {
      element.style.transition = 'all 0.5s ease-in';
      element.style.opacity = '1';
      element.style.transform = 'translateY(0)';
    }, 100);
  });

  // Add hover animations
  document.querySelectorAll('.travian-building-slot').forEach((element) => {
    element.addEventListener('mouseenter', (e) => {
      e.target.style.transform = 'scale(1.05)';
      e.target.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.2)';
    });

    element.addEventListener('mouseleave', (e) => {
      e.target.style.transform = 'scale(1)';
      e.target.style.boxShadow = 'none';
    });
  });
}

function addTravianSounds() {
  // Initialize sound system
  const soundEnabled = localStorage.getItem('travian-sounds') !== 'false';

  if (soundEnabled) {
    // Preload sounds
    preloadSounds();
  }
}

function addTravianNotifications() {
  // Create notification container
  const notificationContainer = document.createElement('div');
  notificationContainer.id = 'travian-notifications';
  notificationContainer.className = 'fixed top-4 right-4 z-50 space-y-2';
  document.body.appendChild(notificationContainer);
}

function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `travian-notification ${type} travian-fade-in`;
  notification.innerHTML = `
        <div class="flex items-center gap-2">
            <span class="notification-icon">${getNotificationIcon(type)}</span>
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;

  document.getElementById('travian-notifications').appendChild(notification);

  // Auto-remove after 5 seconds
  setTimeout(() => {
    if (notification.parentElement) {
      notification.remove();
    }
  }, 5000);
}

function getNotificationIcon(type) {
  const icons = {
    success: '✅',
    error: '❌',
    warning: '⚠️',
    info: 'ℹ️',
  };
  return icons[type] || 'ℹ️';
}

function showTooltip(element, text) {
  const tooltip = document.createElement('div');
  tooltip.className = 'travian-tooltip';
  tooltip.textContent = text;
  tooltip.style.cssText = `
        position: absolute;
        background: var(--travian-dark-gray);
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 12px;
        z-index: 1000;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;

  document.body.appendChild(tooltip);

  const rect = element.getBoundingClientRect();
  tooltip.style.left =
    rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
  tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';

  setTimeout(() => {
    tooltip.style.opacity = '1';
  }, 10);
}

function hideTooltip() {
  const tooltip = document.querySelector('.travian-tooltip');
  if (tooltip) {
    tooltip.remove();
  }
}

function updateLastUpdateTime() {
  const timeElement = document.querySelector('[data-last-update]');
  if (timeElement) {
    timeElement.textContent = new Date().toLocaleTimeString();
  }
}

function updateBuildingProgress(buildingId, progress) {
  const progressElement = document.querySelector(
    `[data-building-id="${buildingId}"] .building-progress`
  );
  if (progressElement) {
    progressElement.style.width = progress + '%';
  }
}

function updateTrainingProgress(unitId, progress) {
  const progressElement = document.querySelector(
    `[data-unit-id="${unitId}"] .training-progress`
  );
  if (progressElement) {
    progressElement.style.width = progress + '%';
  }
}

function updateResourceDisplay(resource) {
  const resourceElement = document.querySelector(
    `[data-resource="${resource.type}"] .resource-amount`
  );
  if (resourceElement) {
    resourceElement.textContent = formatNumber(resource.amount);
  }
}

function updateResourceProduction(production) {
  Object.keys(production).forEach((resource) => {
    const productionElement = document.querySelector(
      `[data-resource="${resource}"] .resource-production`
    );
    if (productionElement) {
      productionElement.textContent =
        '+' + formatNumber(production[resource]) + '/h';
    }
  });
}

function updateVillageDisplay(village) {
  const villageElement = document.querySelector(
    `[data-village-id="${village.id}"]`
  );
  if (villageElement) {
    villageElement.querySelector('.village-population').textContent =
      formatNumber(village.population);
    villageElement.querySelector('.village-culture').textContent = formatNumber(
      village.culture_points
    );
  }
}

function showBuildingAnimation(buildingId) {
  const buildingElement = document.querySelector(
    `[data-building-id="${buildingId}"]`
  );
  if (buildingElement) {
    buildingElement.classList.add('travian-bounce-in');
    setTimeout(() => {
      buildingElement.classList.remove('travian-bounce-in');
    }, 600);
  }
}

function showTrainingAnimation(unitId) {
  const unitElement = document.querySelector(`[data-unit-id="${unitId}"]`);
  if (unitElement) {
    unitElement.classList.add('travian-bounce-in');
    setTimeout(() => {
      unitElement.classList.remove('travian-bounce-in');
    }, 600);
  }
}

function showBattleReportModal(report) {
  // Create battle report modal
  const modal = document.createElement('div');
  modal.className =
    'travian-modal fixed inset-0 z-50 flex items-center justify-center';
  modal.innerHTML = `
        <div class="travian-modal bg-white rounded-lg shadow-lg max-w-2xl w-full mx-4">
            <div class="travian-modal modal-header">
                <h3 class="modal-title">Battle Report</h3>
                <button class="modal-close" onclick="this.closest('.travian-modal').remove()">×</button>
            </div>
            <div class="travian-modal modal-body">
                <div class="battle-report-content">
                    <h4>Battle at ${report.location}</h4>
                    <p>Result: ${report.result}</p>
                    <p>Casualties: ${report.casualties}</p>
                    <p>Loot: ${report.loot}</p>
                </div>
            </div>
            <div class="travian-modal modal-footer">
                <button class="travian-btn" onclick="this.closest('.travian-modal').remove()">Close</button>
            </div>
        </div>
    `;

  document.body.appendChild(modal);
}

function showAchievementModal(achievement) {
  // Create achievement modal
  const modal = document.createElement('div');
  modal.className =
    'travian-modal fixed inset-0 z-50 flex items-center justify-center';
  modal.innerHTML = `
        <div class="travian-modal bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
            <div class="travian-modal modal-header">
                <h3 class="modal-title">🏆 Achievement Unlocked!</h3>
                <button class="modal-close" onclick="this.closest('.travian-modal').remove()">×</button>
            </div>
            <div class="travian-modal modal-body">
                <div class="achievement-content text-center">
                    <div class="achievement-icon text-6xl mb-4">${achievement.icon}</div>
                    <h4 class="text-xl font-bold mb-2">${achievement.name}</h4>
                    <p class="text-gray-600">${achievement.description}</p>
                    <p class="text-sm text-gray-500 mt-2">Reward: ${achievement.reward}</p>
                </div>
            </div>
            <div class="travian-modal modal-footer">
                <button class="travian-btn" onclick="this.closest('.travian-modal').remove()">Awesome!</button>
            </div>
        </div>
    `;

  document.body.appendChild(modal);
}

function showBattleReportNotification(report) {
  showNotification(
    `Battle report: ${report.result} at ${report.location}`,
    'warning'
  );
}

function showAchievementNotification(achievement) {
  showNotification(`Achievement unlocked: ${achievement.name}`, 'success');
}

function playSound(soundName) {
  const soundEnabled = localStorage.getItem('travian-sounds') !== 'false';
  if (!soundEnabled) return;

  // Create audio element
  const audio = new Audio(`/sounds/${soundName}.mp3`);
  audio.volume = 0.3;
  audio.play().catch(() => {
    // Fallback to system beep
    console.log(`🔊 Playing sound: ${soundName}`);
  });
}

function preloadSounds() {
  const sounds = [
    'building-complete',
    'training-complete',
    'battle-report',
    'achievement',
    'diplomatic-event',
  ];

  sounds.forEach((sound) => {
    const audio = new Audio(`/sounds/${sound}.mp3`);
    audio.preload = 'auto';
  });
}

function formatNumber(number) {
  if (number >= 1000000) {
    return (number / 1000000).toFixed(1) + 'M';
  } else if (number >= 1000) {
    return (number / 1000).toFixed(1) + 'K';
  }
  return number.toString();
}

// Export functions for global access
window.TravianRealtime = {
  showNotification,
  playSound,
  formatNumber,
  showTooltip,
  hideTooltip,
};
