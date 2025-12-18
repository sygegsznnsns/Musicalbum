/**
 * 背景音乐播放器
 * 功能：自动播放、循环播放、音量控制、播放/暂停控制
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
        
        if (!audio) return;
        
        // 从localStorage恢复设置
        const savedVolume = localStorage.getItem('backgroundMusicVolume');
        const savedPlaying = localStorage.getItem('backgroundMusicPlaying');
        
        if (savedVolume !== null) {
            audio.volume = parseFloat(savedVolume);
            volumeSlider.value = savedVolume;
            updateVolumeIcon(parseFloat(savedVolume));
        }
        
        // 播放/暂停按钮
        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', function() {
                if (audio.paused) {
                    audio.play().then(function() {
                        playPauseBtn.innerHTML = '<span class="music-icon">⏸</span>';
                        playPauseBtn.setAttribute('aria-label', '暂停背景音乐');
                        localStorage.setItem('backgroundMusicPlaying', 'true');
                    }).catch(function(error) {
                        console.log('自动播放被阻止:', error);
                        // 显示提示信息
                        if (musicInfo) {
                            musicInfo.textContent = '点击播放按钮开始播放音乐';
                            musicInfo.style.display = 'block';
                        }
                    });
                } else {
                    audio.pause();
                    playPauseBtn.innerHTML = '<span class="music-icon">▶</span>';
                    playPauseBtn.setAttribute('aria-label', '播放背景音乐');
                    localStorage.setItem('backgroundMusicPlaying', 'false');
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
        
        // 更新音量图标
        function updateVolumeIcon(volume) {
            if (!volumeIcon) return;
            if (volume === 0) {
                volumeIcon.textContent = '🔇';
            } else if (volume < 0.5) {
                volumeIcon.textContent = '🔉';
            } else {
                volumeIcon.textContent = '🔊';
            }
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
            }
        });
        
        // 如果之前是播放状态，尝试自动播放（需要用户交互后才能自动播放）
        if (savedPlaying === 'true') {
            // 注意：现代浏览器需要用户交互后才能自动播放
            // 这里只是恢复状态，实际播放需要用户点击
        }
        
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

