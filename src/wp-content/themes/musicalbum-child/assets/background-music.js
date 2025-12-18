/**
 * 背景音乐播放器
 * 功能：跨页面连续播放、自动播放、循环播放、音量控制、播放/暂停控制
 */
(function() {
    'use strict';
    
    // 存储键名
    const STORAGE_KEYS = {
        volume: 'backgroundMusicVolume',
        playing: 'backgroundMusicPlaying',
        currentTime: 'backgroundMusicCurrentTime',
        audioUrl: 'backgroundMusicAudioUrl'
    };
    
    let audio = null;
    let playPauseBtn = null;
    let volumeSlider = null;
    let volumeIcon = null;
    let musicInfo = null;
    let isUserPaused = false; // 标记用户是否手动暂停
    
    // 保存播放状态到localStorage
    function savePlayState() {
        if (!audio) return;
        try {
            localStorage.setItem(STORAGE_KEYS.playing, audio.paused ? 'false' : 'true');
            localStorage.setItem(STORAGE_KEYS.currentTime, audio.currentTime.toString());
            localStorage.setItem(STORAGE_KEYS.audioUrl, audio.src);
        } catch (e) {
            console.warn('保存播放状态失败:', e);
        }
    }
    
    // 在页面卸载前保存状态
    function handleBeforeUnload() {
        savePlayState();
    }
    
    // 在页面可见性变化时保存状态
    function handleVisibilityChange() {
        if (document.hidden) {
            savePlayState();
        }
    }
    
    // 定期保存播放时间（每2秒）
    function startAutoSave() {
        setInterval(function() {
            if (audio && !audio.paused) {
                savePlayState();
            }
        }, 2000);
    }
    
    // 恢复播放状态
    function restorePlayState() {
        if (!audio) return;
        
        try {
            const savedVolume = localStorage.getItem(STORAGE_KEYS.volume);
            const savedPlaying = localStorage.getItem(STORAGE_KEYS.playing);
            const savedCurrentTime = localStorage.getItem(STORAGE_KEYS.currentTime);
            const savedAudioUrl = localStorage.getItem(STORAGE_KEYS.audioUrl);
            
            // 恢复音量
            if (savedVolume !== null && volumeSlider) {
                const volume = parseFloat(savedVolume);
                audio.volume = volume;
                volumeSlider.value = volume;
                updateVolumeIcon(volume);
            }
            
            // 检查是否是同一个音频文件
            const currentAudioUrl = audio.src || audio.currentSrc;
            if (savedAudioUrl && currentAudioUrl && savedAudioUrl === currentAudioUrl) {
                // 恢复播放时间
                if (savedCurrentTime !== null) {
                    const time = parseFloat(savedCurrentTime);
                    if (time > 0 && time < audio.duration) {
                        audio.currentTime = time;
                    }
                }
                
                // 恢复播放状态（如果之前是播放状态且用户没有手动暂停）
                if (savedPlaying === 'true' && !isUserPaused) {
                    // 等待音频加载完成后再播放
                    if (audio.readyState >= 2) {
                        // 音频已加载足够数据
                        audio.play().then(function() {
                            updatePlayButton(true);
                        }).catch(function(error) {
                            console.log('自动恢复播放被阻止:', error);
                            // 如果自动播放失败，更新按钮状态但不播放
                            updatePlayButton(false);
                        });
                    } else {
                        // 等待音频加载
                        audio.addEventListener('canplay', function playWhenReady() {
                            audio.removeEventListener('canplay', playWhenReady);
                            if (savedPlaying === 'true' && !isUserPaused) {
                                audio.play().then(function() {
                                    updatePlayButton(true);
                                }).catch(function(error) {
                                    console.log('自动恢复播放被阻止:', error);
                                    updatePlayButton(false);
                                });
                            }
                        }, { once: true });
                    }
                } else {
                    updatePlayButton(false);
                }
            } else {
                // 不同的音频文件，重置状态
                isUserPaused = false;
                updatePlayButton(false);
            }
        } catch (e) {
            console.warn('恢复播放状态失败:', e);
        }
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
    
    // 等待DOM加载完成
    document.addEventListener('DOMContentLoaded', function() {
        audio = document.getElementById('background-music');
        playPauseBtn = document.getElementById('music-play-pause');
        volumeSlider = document.getElementById('music-volume');
        volumeIcon = document.getElementById('music-volume-icon');
        musicInfo = document.getElementById('music-info');
        
        if (!audio) return;
        
        // 检查用户是否手动暂停（从localStorage读取）
        const savedPlaying = localStorage.getItem(STORAGE_KEYS.playing);
        isUserPaused = savedPlaying === 'false';
        
        // 播放/暂停按钮
        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', function() {
                if (audio.paused) {
                    isUserPaused = false;
                    audio.play().then(function() {
                        updatePlayButton(true);
                        savePlayState();
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
                    isUserPaused = true;
                    audio.pause();
                    updatePlayButton(false);
                    savePlayState();
                }
            });
        }
        
        // 音量控制
        if (volumeSlider) {
            volumeSlider.addEventListener('input', function() {
                const volume = parseFloat(this.value);
                audio.volume = volume;
                localStorage.setItem(STORAGE_KEYS.volume, volume);
                updateVolumeIcon(volume);
            });
        }
        
        // 音频播放结束，自动重新开始（循环播放）
        audio.addEventListener('ended', function() {
            audio.currentTime = 0;
            if (!isUserPaused) {
                audio.play();
            }
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
        
        // 音频时间更新时保存（用于跨页面恢复）
        audio.addEventListener('timeupdate', function() {
            // 每5秒保存一次，避免频繁写入
            if (Math.floor(audio.currentTime) % 5 === 0 && !audio.paused) {
                savePlayState();
            }
        });
        
        // 恢复播放状态
        restorePlayState();
        
        // 开始自动保存
        startAutoSave();
        
        // 监听页面卸载和可见性变化
        window.addEventListener('beforeunload', handleBeforeUnload);
        document.addEventListener('visibilitychange', handleVisibilityChange);
        
        // 监听页面卸载（使用pagehide作为备用）
        window.addEventListener('pagehide', handleBeforeUnload);
        
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

