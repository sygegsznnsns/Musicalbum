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
        const musicSelect = document.getElementById('music-select');
        const toggleHideBtn = document.getElementById('music-toggle-hide');
        const showButton = document.getElementById('music-show-button');
        
        if (!audio) return;
        
        // 获取预设音乐数据
        const musicData = window.backgroundMusicData || {};
        const presets = musicData.presets || {};
        let wasPlaying = false; // 记录切换前是否在播放
        
        // 拖拽功能
        if (musicPlayer) {
            let isDragging = false;
            let currentX = 0;
            let currentY = 0;
            let initialX = 0;
            let initialY = 0;
            let xOffset = 0;
            let yOffset = 0;
            let rafId = null;
            
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
            
            // 设置位置（使用requestAnimationFrame优化性能）
            function setTranslate(xPos, yPos, el) {
                // 拖拽时禁用transition，确保实时跟随
                el.style.transition = 'none';
                el.style.transform = `translate(${xPos}px, ${yPos}px)`;
            }
            
            // 保存位置
            function savePosition() {
                const position = {
                    x: xOffset,
                    y: yOffset
                };
                localStorage.setItem('backgroundMusicPosition', JSON.stringify(position));
                
                // 如果播放器是隐藏状态，同步更新展开按钮位置
                if (musicPlayer && musicPlayer.classList.contains('hidden') && showButton) {
                    showButton.style.transform = `translate(${xOffset}px, ${yOffset}px)`;
                }
            }
            
            // 鼠标按下事件
            musicPlayer.addEventListener('mousedown', function(e) {
                // 如果点击的是按钮、滑块或选择框，不启动拖拽
                if (e.target.closest('button') || e.target.closest('input[type="range"]') || e.target.closest('select')) {
                    return;
                }
                
                initialX = e.clientX - xOffset;
                initialY = e.clientY - yOffset;
                
                if (e.target === musicPlayer || e.target.closest('#background-music-player')) {
                    isDragging = true;
                    musicPlayer.classList.add('dragging');
                }
            });
            
            // 鼠标移动事件（使用requestAnimationFrame优化）
            document.addEventListener('mousemove', function(e) {
                if (isDragging) {
                    e.preventDefault();
                    
                    // 取消之前的动画帧请求
                    if (rafId !== null) {
                        cancelAnimationFrame(rafId);
                    }
                    
                    // 使用requestAnimationFrame确保流畅
                    rafId = requestAnimationFrame(function() {
                        currentX = e.clientX - initialX;
                        currentY = e.clientY - initialY;
                        
                        xOffset = currentX;
                        yOffset = currentY;
                        
                        setTranslate(currentX, currentY, musicPlayer);
                    });
                }
            });
            
            // 鼠标释放事件
            document.addEventListener('mouseup', function() {
                if (isDragging) {
                    isDragging = false;
                    musicPlayer.classList.remove('dragging');
                    // 恢复transition
                    musicPlayer.style.transition = '';
                    savePosition();
                }
            });
            
            // 触摸事件支持（移动设备）
            musicPlayer.addEventListener('touchstart', function(e) {
                if (e.target.closest('button') || e.target.closest('input[type="range"]') || e.target.closest('select')) {
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
                    
                    // 取消之前的动画帧请求
                    if (rafId !== null) {
                        cancelAnimationFrame(rafId);
                    }
                    
                    const touch = e.touches[0];
                    
                    // 使用requestAnimationFrame确保流畅
                    rafId = requestAnimationFrame(function() {
                        currentX = touch.clientX - initialX;
                        currentY = touch.clientY - initialY;
                        
                        xOffset = currentX;
                        yOffset = currentY;
                        
                        setTranslate(currentX, currentY, musicPlayer);
                    });
                }
            });
            
            document.addEventListener('touchend', function() {
                if (isDragging) {
                    isDragging = false;
                    musicPlayer.classList.remove('dragging');
                    // 恢复transition
                    musicPlayer.style.transition = '';
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
                // 暂停图标：两条平行线
                playPauseBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><rect x="4" y="2" width="3" height="12" rx="0.5"/><rect x="9" y="2" width="3" height="12" rx="0.5"/></svg>';
                playPauseBtn.setAttribute('aria-label', '暂停背景音乐');
                playPauseBtn.classList.add('playing');
            } else {
                // 播放图标：三角形
                playPauseBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M3 2.5v11l9-5.5z"/></svg>';
                playPauseBtn.setAttribute('aria-label', '播放背景音乐');
                playPauseBtn.classList.remove('playing');
            }
        }
        
        // 初始化按钮状态（根据音频当前状态）
        if (playPauseBtn) {
            updatePlayButton(!audio.paused);
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
                // 检查是否有音频源（是否选择了音乐）
                if (!audio.src || audio.src === window.location.href) {
                    if (musicInfo) {
                        musicInfo.textContent = '请先选择要播放的音乐';
                        musicInfo.style.display = 'block';
                        musicInfo.style.opacity = '1';
                        setTimeout(function() {
                            musicInfo.style.opacity = '0';
                            setTimeout(function() {
                                musicInfo.style.display = 'none';
                            }, 500);
                        }, 2000);
                    }
                    return;
                }
                
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
        
        // 音乐切换功能
        if (musicSelect && Object.keys(presets).length > 1) {
            musicSelect.addEventListener('change', function() {
                const selectedId = this.value;
                
                // 如果选择的是空选项（"无音乐"），停止播放并清空音频源
                if (!selectedId || !presets[selectedId]) {
                    audio.pause();
                    audio.currentTime = 0;
                    audio.src = ''; // 清空音频源，表示没有选择音乐
                    audio.load(); // 重新加载以清空音频
                    updatePlayButton(false);
                    if (musicInfo) {
                        musicInfo.textContent = '已选择：无音乐';
                        musicInfo.style.display = 'block';
                        musicInfo.style.opacity = '1';
                        setTimeout(function() {
                            musicInfo.style.opacity = '0';
                            setTimeout(function() {
                                musicInfo.style.display = 'none';
                            }, 500);
                        }, 2000);
                    }
                    return;
                }
                
                const selectedMusic = presets[selectedId];
                const newUrl = selectedMusic.url;
                
                if (!newUrl) {
                    audio.pause();
                    audio.currentTime = 0;
                    updatePlayButton(false);
                    if (musicInfo) {
                        musicInfo.textContent = '音乐URL无效';
                        musicInfo.style.display = 'block';
                        musicInfo.style.opacity = '1';
                        setTimeout(function() {
                            musicInfo.style.opacity = '0';
                            setTimeout(function() {
                                musicInfo.style.display = 'none';
                            }, 500);
                        }, 2000);
                    }
                    return;
                }
                
                // 记录当前播放状态和音量
                wasPlaying = !audio.paused;
                const currentVolume = audio.volume;
                const currentTime = audio.currentTime;
                
                // 暂停当前音乐
                audio.pause();
                
                // 更新音频源
                audio.src = newUrl;
                audio.load(); // 重新加载音频
                
                // 恢复音量和播放位置（如果可能）
                audio.volume = currentVolume;
                
                // 如果之前正在播放，自动播放新音乐
                if (wasPlaying) {
                    audio.play().then(function() {
                        updatePlayButton(true);
                        if (musicInfo) {
                            musicInfo.textContent = '已切换到：' + selectedMusic.name;
                            musicInfo.style.display = 'block';
                            musicInfo.style.opacity = '1';
                            setTimeout(function() {
                                musicInfo.style.opacity = '0';
                                setTimeout(function() {
                                    musicInfo.style.display = 'none';
                                }, 500);
                            }, 2000);
                        }
                    }).catch(function(error) {
                        console.log('自动播放被阻止:', error);
                        updatePlayButton(false);
                        if (musicInfo) {
                            musicInfo.textContent = '请点击播放按钮开始播放';
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
                    updatePlayButton(false);
                }
            });
        }
        
        // 音频播放结束，自动重新开始（循环播放）
        audio.addEventListener('ended', function() {
            audio.currentTime = 0;
            audio.play();
        });
        
        // 音频加载错误处理
        audio.addEventListener('error', function() {
            // 检查是否是因为选择了"无音乐"（音频源为空）
            if (!audio.src || audio.src === '' || audio.src === window.location.href) {
                if (musicInfo) {
                    musicInfo.textContent = '已选择：无音乐';
                    musicInfo.style.display = 'block';
                    musicInfo.style.opacity = '1';
                    setTimeout(function() {
                        musicInfo.style.opacity = '0';
                        setTimeout(function() {
                            musicInfo.style.display = 'none';
                        }, 500);
                    }, 2000);
                }
            } else {
                // 真正的音频文件加载失败
                console.error('音频加载失败，请检查音频文件路径');
                if (musicInfo) {
                    musicInfo.textContent = '音频文件加载失败，请检查文件路径';
                    musicInfo.style.display = 'block';
                    musicInfo.style.opacity = '1';
                    setTimeout(function() {
                        musicInfo.style.opacity = '0';
                        setTimeout(function() {
                            musicInfo.style.display = 'none';
                        }, 500);
                    }, 2000);
                }
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
        
        // 隐藏/显示播放器功能
        function hidePlayer() {
            if (!musicPlayer || !showButton) return;
            
            // 优先使用展开按钮保存的位置，如果没有则使用播放器位置
            const savedShowButtonPosition = localStorage.getItem('backgroundMusicShowButtonPosition');
            let pos = { x: 0, y: 0 };
            
            if (savedShowButtonPosition) {
                try {
                    pos = JSON.parse(savedShowButtonPosition);
                } catch (e) {
                    console.warn('恢复展开按钮位置失败:', e);
                }
            }
            
            // 如果展开按钮没有保存的位置，使用播放器位置
            if (pos.x === 0 && pos.y === 0) {
                const savedPosition = localStorage.getItem('backgroundMusicPosition');
                if (savedPosition) {
                    try {
                        pos = JSON.parse(savedPosition);
                    } catch (e) {
                        console.warn('恢复播放器位置失败:', e);
                    }
                }
            }
            
            // 设置展开按钮位置
            if (pos.x !== undefined && pos.y !== undefined) {
                showButton.style.transform = `translate(${pos.x}px, ${pos.y}px)`;
            } else {
                showButton.style.transform = 'translate(0, 0)';
            }
            
            musicPlayer.classList.add('hidden');
            showButton.style.display = 'flex';
            localStorage.setItem('backgroundMusicHidden', 'true');
        }
        
        function showPlayer() {
            if (!musicPlayer || !showButton) return;
            musicPlayer.classList.remove('hidden');
            showButton.style.display = 'none';
            localStorage.setItem('backgroundMusicHidden', 'false');
        }
        
        // 隐藏按钮点击事件
        if (toggleHideBtn) {
            toggleHideBtn.addEventListener('click', function(e) {
                e.stopPropagation(); // 防止触发拖拽
                hidePlayer();
            });
        }
        
        // 展开按钮拖拽功能
        if (showButton) {
            let isShowButtonDragging = false;
            let showButtonCurrentX = 0;
            let showButtonCurrentY = 0;
            let showButtonInitialX = 0;
            let showButtonInitialY = 0;
            let showButtonXOffset = 0;
            let showButtonYOffset = 0;
            let showButtonRafId = null;
            let showButtonClickTime = 0;
            let showButtonStartX = 0;
            let showButtonStartY = 0;
            
            // 恢复展开按钮位置
            const savedShowButtonPosition = localStorage.getItem('backgroundMusicShowButtonPosition');
            if (savedShowButtonPosition) {
                try {
                    const pos = JSON.parse(savedShowButtonPosition);
                    if (pos.x !== undefined && pos.y !== undefined) {
                        showButtonXOffset = pos.x;
                        showButtonYOffset = pos.y;
                        showButton.style.transform = `translate(${pos.x}px, ${pos.y}px)`;
                    }
                } catch (e) {
                    console.warn('恢复展开按钮位置失败:', e);
                }
            }
            
            // 设置展开按钮位置
            function setShowButtonTranslate(xPos, yPos, el) {
                el.style.transition = 'none';
                el.style.transform = `translate(${xPos}px, ${yPos}px)`;
            }
            
            // 保存展开按钮位置
            function saveShowButtonPosition() {
                localStorage.setItem('backgroundMusicShowButtonPosition', JSON.stringify({
                    x: showButtonXOffset,
                    y: showButtonYOffset
                }));
            }
            
            // 展开按钮鼠标按下事件
            showButton.addEventListener('mousedown', function(e) {
                showButtonClickTime = Date.now();
                showButtonStartX = e.clientX;
                showButtonStartY = e.clientY;
                showButtonInitialX = e.clientX - showButtonXOffset;
                showButtonInitialY = e.clientY - showButtonYOffset;
                
                if (e.target === showButton || e.target.closest('#music-show-button')) {
                    isShowButtonDragging = true;
                    showButton.classList.add('dragging');
                }
            });
            
            // 展开按钮鼠标移动事件
            document.addEventListener('mousemove', function(e) {
                if (isShowButtonDragging) {
                    e.preventDefault();
                    
                    if (showButtonRafId !== null) {
                        cancelAnimationFrame(showButtonRafId);
                    }
                    
                    showButtonRafId = requestAnimationFrame(function() {
                        showButtonCurrentX = e.clientX - showButtonInitialX;
                        showButtonCurrentY = e.clientY - showButtonInitialY;
                        
                        showButtonXOffset = showButtonCurrentX;
                        showButtonYOffset = showButtonCurrentY;
                        
                        setShowButtonTranslate(showButtonCurrentX, showButtonCurrentY, showButton);
                    });
                }
            });
            
            // 展开按钮鼠标释放事件
            document.addEventListener('mouseup', function(e) {
                if (isShowButtonDragging) {
                    isShowButtonDragging = false;
                    showButton.classList.remove('dragging');
                    showButton.style.transition = '';
                    
                    // 判断是点击还是拖拽（移动距离小于5px且时间小于200ms认为是点击）
                    const endX = e.clientX;
                    const endY = e.clientY;
                    const moveDistance = Math.sqrt(
                        Math.pow(showButtonStartX - endX, 2) +
                        Math.pow(showButtonStartY - endY, 2)
                    );
                    const clickDuration = Date.now() - showButtonClickTime;
                    
                    if (moveDistance < 5 && clickDuration < 200) {
                        // 点击事件
                        showPlayer();
                    } else {
                        // 拖拽事件，保存位置
                        saveShowButtonPosition();
                    }
                }
            });
            
            // 展开按钮触摸事件支持
            showButton.addEventListener('touchstart', function(e) {
                const touch = e.touches[0];
                showButtonClickTime = Date.now();
                showButtonStartX = touch.clientX;
                showButtonStartY = touch.clientY;
                showButtonInitialX = touch.clientX - showButtonXOffset;
                showButtonInitialY = touch.clientY - showButtonYOffset;
                
                if (e.target === showButton || e.target.closest('#music-show-button')) {
                    isShowButtonDragging = true;
                    showButton.classList.add('dragging');
                    e.preventDefault();
                }
            });
            
            document.addEventListener('touchmove', function(e) {
                if (isShowButtonDragging) {
                    e.preventDefault();
                    
                    if (showButtonRafId !== null) {
                        cancelAnimationFrame(showButtonRafId);
                    }
                    
                    const touch = e.touches[0];
                    
                    showButtonRafId = requestAnimationFrame(function() {
                        showButtonCurrentX = touch.clientX - showButtonInitialX;
                        showButtonCurrentY = touch.clientY - showButtonInitialY;
                        
                        showButtonXOffset = showButtonCurrentX;
                        showButtonYOffset = showButtonCurrentY;
                        
                        setShowButtonTranslate(showButtonCurrentX, showButtonCurrentY, showButton);
                    });
                }
            });
            
            document.addEventListener('touchend', function(e) {
                if (isShowButtonDragging) {
                    isShowButtonDragging = false;
                    showButton.classList.remove('dragging');
                    showButton.style.transition = '';
                    
                    // 判断是点击还是拖拽
                    const endX = e.changedTouches ? e.changedTouches[0].clientX : showButtonStartX;
                    const endY = e.changedTouches ? e.changedTouches[0].clientY : showButtonStartY;
                    const moveDistance = Math.sqrt(
                        Math.pow(showButtonStartX - endX, 2) +
                        Math.pow(showButtonStartY - endY, 2)
                    );
                    const clickDuration = Date.now() - showButtonClickTime;
                    
                    if (moveDistance < 5 && clickDuration < 200) {
                        showPlayer();
                    } else {
                        saveShowButtonPosition();
                    }
                }
            });
        }
        
        // 恢复隐藏状态
        const isHidden = localStorage.getItem('backgroundMusicHidden');
        if (isHidden === 'true' && musicPlayer && showButton) {
            // 延迟执行，确保样式已加载
            setTimeout(function() {
                hidePlayer();
            }, 100);
        }
    });
})();

