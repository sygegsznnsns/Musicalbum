/**
 * 背景音乐播放器
 * 功能：循环播放、音量控制、播放/暂停控制
 */
(function() {
    'use strict';
    
    // 等待DOM加载完成
    document.addEventListener('DOMContentLoaded', function() {
        const audio = document.getElementById('background-music');
        const playPauseBtn = document.getElementById('music-play-pause');
        const volumeSlider = document.getElementById('music-volume');
        const volumeIcon = document.getElementById('music-volume-icon');
        const musicInfo = document.getElementById('music-info');
        const musicPlayer = document.getElementById('background-music-player');
        
        if (!audio) return;
        
        // 拖拽功能
        if (musicPlayer) {
            let isDragging = false;
            let currentX = 0;
            let currentY = 0;
            let initialX = 0;
            let initialY = 0;
            let xOffset = 0;
            let yOffset = 0;
            
            // 恢复保存的位置
            const savedPosition = localStorage.getItem('backgroundMusicPosition');
            if (savedPosition) {
                try {
                    const pos = JSON.parse(savedPosition);
                    if (pos.x !== undefined && pos.y !== undefined) {
                        xOffset = pos.x;
                        yOffset = pos.y;
                        setTranslate(xOffset, yOffset, musicPlayer);
                    }
                } catch (e) {
                    console.warn('恢复位置失败:', e);
                }
            }
            
            // 设置位置
            function setTranslate(xPos, yPos, el) {
                el.style.transform = `translate(${xPos}px, ${yPos}px)`;
            }
            
            // 保存位置
            function savePosition() {
                localStorage.setItem('backgroundMusicPosition', JSON.stringify({
                    x: xOffset,
                    y: yOffset
                }));
            }
            
            // 鼠标按下事件
            musicPlayer.addEventListener('mousedown', function(e) {
                // 如果点击的是按钮或滑块，不启动拖拽
                if (e.target.closest('button') || e.target.closest('input[type="range"]')) {
                    return;
                }
                
                initialX = e.clientX - xOffset;
                initialY = e.clientY - yOffset;
                
                if (e.target === musicPlayer || e.target.closest('#background-music-player')) {
                    isDragging = true;
                    musicPlayer.classList.add('dragging');
                }
            });
            
            // 鼠标移动事件
            document.addEventListener('mousemove', function(e) {
                if (isDragging) {
                    e.preventDefault();
                    currentX = e.clientX - initialX;
                    currentY = e.clientY - initialY;
                    
                    xOffset = currentX;
                    yOffset = currentY;
                    
                    setTranslate(currentX, currentY, musicPlayer);
                }
            });
            
            // 鼠标释放事件
            document.addEventListener('mouseup', function() {
                if (isDragging) {
                    isDragging = false;
                    musicPlayer.classList.remove('dragging');
                    savePosition();
                }
            });
            
            // 触摸事件支持（移动设备）
            musicPlayer.addEventListener('touchstart', function(e) {
                if (e.target.closest('button') || e.target.closest('input[type="range"]')) {
                    return;
                }
                
                const touch = e.touches[0];
                initialX = touch.clientX - xOffset;
                initialY = touch.clientY - yOffset;
                
                if (e.target === musicPlayer || e.target.closest('#background-music-player')) {
                    isDragging = true;
                    musicPlayer.classList.add('dragging');
                    e.preventDefault();
                }
            });
            
            document.addEventListener('touchmove', function(e) {
                if (isDragging) {
                    e.preventDefault();
                    const touch = e.touches[0];
                    currentX = touch.clientX - initialX;
                    currentY = touch.clientY - initialY;
                    
                    xOffset = currentX;
                    yOffset = currentY;
                    
                    setTranslate(currentX, currentY, musicPlayer);
                }
            });
            
            document.addEventListener('touchend', function() {
                if (isDragging) {
                    isDragging = false;
                    musicPlayer.classList.remove('dragging');
                    savePosition();
                }
            });
        }
        
        // 从localStorage恢复音量设置
        const savedVolume = localStorage.getItem('backgroundMusicVolume');
        if (savedVolume !== null && volumeSlider) {
            const volume = parseFloat(savedVolume);
            audio.volume = volume;
            volumeSlider.value = volume;
            updateVolumeIcon(volume);
        }
        
        // 更新播放按钮状态
        function updatePlayButton(isPlaying) {
            if (!playPauseBtn) return;
            if (isPlaying) {
                playPauseBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="4" y="2" width="3" height="12"/><rect x="9" y="2" width="3" height="12"/></svg>';
                playPauseBtn.setAttribute('aria-label', '暂停背景音乐');
                playPauseBtn.classList.add('playing');
            } else {
                playPauseBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M3 2.5v11l9-5.5z"/></svg>';
                playPauseBtn.setAttribute('aria-label', '播放背景音乐');
                playPauseBtn.classList.remove('playing');
            }
        }
        
        // 更新音量图标
        function updateVolumeIcon(volume) {
            if (!volumeIcon) return;
            let iconSvg = '';
            if (volume === 0) {
                iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>';
            } else if (volume < 0.5) {
                iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.5 12c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM5 9v6h4l5 5V4L9 9H5z"/></svg>';
            } else {
                iconSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>';
            }
            volumeIcon.innerHTML = iconSvg;
        }
        
        // 播放/暂停按钮
        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', function() {
                if (audio.paused) {
                    audio.play().then(function() {
                        updatePlayButton(true);
                    }).catch(function(error) {
                        console.log('播放被阻止:', error);
                        if (musicInfo) {
                            musicInfo.textContent = '点击播放按钮开始播放音乐';
                            musicInfo.style.display = 'block';
                            musicInfo.style.opacity = '1';
                            setTimeout(function() {
                                musicInfo.style.opacity = '0';
                                setTimeout(function() {
                                    musicInfo.style.display = 'none';
                                }, 500);
                            }, 2000);
                        }
                    });
                } else {
                    audio.pause();
                    updatePlayButton(false);
                }
            });
        }
        
        // 音量控制
        if (volumeSlider) {
            volumeSlider.addEventListener('input', function() {
                const volume = parseFloat(this.value);
                audio.volume = volume;
                localStorage.setItem('backgroundMusicVolume', volume);
                updateVolumeIcon(volume);
            });
        }
        
        // 音频播放结束，自动重新开始（循环播放）
        audio.addEventListener('ended', function() {
            audio.currentTime = 0;
            audio.play();
        });
        
        // 音频加载错误处理
        audio.addEventListener('error', function() {
            console.error('音频加载失败，请检查音频文件路径');
            if (musicInfo) {
                musicInfo.textContent = '音频文件加载失败，请检查文件路径';
                musicInfo.style.display = 'block';
                musicInfo.style.opacity = '1';
            }
        });
        
        // 显示/隐藏音乐信息
        if (musicInfo) {
            setTimeout(function() {
                musicInfo.style.opacity = '0';
                setTimeout(function() {
                    musicInfo.style.display = 'none';
                }, 500);
            }, 3000);
        }
    });
})();

