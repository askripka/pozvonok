// Шаги алгоритма ECMA-262, 5-е издание, 15.4.4.14
// Ссылка (en): http://es5.github.io/#x15.4.4.14
// Ссылка (ru): http://es5.javascript.ru/x15.4.html#x15.4.4.14
if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function (searchElement, fromIndex) {
        var k;

        // 1. Положим O равным результату вызова ToObject с передачей ему
        //    значения this в качестве аргумента.
        if (this == null) {
            throw new TypeError('"this" is null or not defined');
        }

        var O = Object(this);

        // 2. Положим lenValue равным результату вызова внутреннего метода Get
        //    объекта O с аргументом "length".
        // 3. Положим len равным ToUint32(lenValue).
        var len = O.length >>> 0;

        // 4. Если len равен 0, вернём -1.
        if (len === 0) {
            return -1;
        }

        // 5. Если был передан аргумент fromIndex, положим n равным
        //    ToInteger(fromIndex); иначе положим n равным 0.
        var n = +fromIndex || 0;

        if (Math.abs(n) === Infinity) {
            n = 0;
        }

        // 6. Если n >= len, вернём -1.
        if (n >= len) {
            return -1;
        }

        // 7. Если n >= 0, положим k равным n.
        // 8. Иначе, n<0, положим k равным len - abs(n).
        //    Если k меньше нуля 0, положим k равным 0.
        k = Math.max(n >= 0 ? n : len - Math.abs(n), 0);

        // 9. Пока k < len, будем повторять
        while (k < len) {
            // a. Положим Pk равным ToString(k).
            //   Это неявное преобразование для левостороннего операнда в операторе in
            // b. Положим kPresent равным результату вызова внутреннего метода
            //    HasProperty объекта O с аргументом Pk.
            //   Этот шаг может быть объединён с шагом c
            // c. Если kPresent равен true, выполним
            //    i.  Положим elementK равным результату вызова внутреннего метода Get
            //        объекта O с аргументом ToString(k).
            //   ii.  Положим same равным результату применения
            //        Алгоритма строгого сравнения на равенство между
            //        searchElement и elementK.
            //  iii.  Если same равен true, вернём k.
            if (k in O && O[k] === searchElement) {
                return k;
            }
            k++;
        }
        return -1;
    };
}

+function ($, window) {
    'use strict';
    var app = {
        name: 'Infinity',
        version: '1.0.0'
    };

    app.defaults = {
        sidebar: {
            folded: false,
            theme: 'light',
            themes: ['light', 'dark']
        },
        navbar: {
            theme: 'primary',
            themes: ['primary', 'success', 'warning', 'danger', 'pink', 'purple', 'inverse', 'dark']
        }
    };

    app.$body = $('body');
    app.$sidebar = $('#app-aside');
    app.$navbar = $('#app-navbar');
    app.$main = $('#app-main');

    app.settings = app.defaults;

    var appSettings = app.name + "Settings";
    app.storage = $.localStorage;

    if (app.storage.isEmpty(appSettings)) {
        app.storage.set(appSettings, app.settings);
    } else {
        app.settings = app.storage.get(appSettings);
    }

    app.saveSettings = function () {
        app.storage.set(appSettings, app.settings);
    };

    // initialize navbar
    app.$navbar.removeClass('primary').addClass(app.settings.navbar.theme).addClass('in');
    app.$body.removeClass('theme-primary').addClass('theme-' + app.settings.navbar.theme);

    // initialize sidebar
    app.$sidebar.removeClass('light').addClass(app.settings.sidebar.theme).addClass('in');
    app.settings.sidebar.folded
    && app.$sidebar.addClass('folded')
    && app.$body.addClass('sb-folded')
    && $('#aside-fold').removeClass('is-active');

    // initialize main
    app.$main.addClass('in');

    app.init = function () {

        $('[data-plugin]').plugins();
        $('.scrollable-container').perfectScrollbar();
        $('.sf-menu').superfish();

        // load some needed libs listed at: LIBS.others => library.js
        var loadingLibs = loader.load(LIBS["others"]);
        loadingLibs.done(function () {
            $('[data-switchery]').each(function () {
                var $this = $(this),
                    color = $this.attr('data-color') || '#188ae2',
                    secondaryColor = $this.attr('data-secondaryColor') || '#dfdfdf',
                    jackColor = $this.attr('data-jackColor') || '#ffffff',
                    size = $this.attr('data-size') || 'default';

                new Switchery(this, {
                    color: color,
                    secondaryColor: secondaryColor,
                    size: size,
                    jackColor: jackColor
                });
            });
        });

        $('#tzSetter').on('change', function () {
            $('#tzSetterForm').submit();
        });

        //var loadingLibs3 = loader.load(LIBS["sisyphus"]);
        //loadingLibs3.done(function () {
        //    $('form.sisiph').sisyphus();
        //});

        if ($(".task-pieprogress").length) {
            setInterval(function () {
                try {
                    $.post("/ajax.php", {'get_tasks_progress': 1}, function (data) {
                        $.each(data, function (index, value) {
                            var taskStat = $(".task-pieprogress[data-task-id=" + index + "]");
                            if (taskStat.length) {
                                taskStat.circleProgress('value', value);
                                taskStat.find(".task-pieprogress-val").text(Math.floor(value * 100));
                            } else {
                                console.log(taskStat);
                            }
                        });

                    }, "JSON");
                } catch (err) {
                    console.log(err);
                }
            }, 10000);
        }

        if ($(".task-progress-bar").length) {
            var taskID = $(".task-progress-bar").attr("data-task-id");
            setInterval(function () {
                try {
                    $.post("/ajax.php", {'get_task_progress': taskID}, function (data) {
                        var $taskProgressBar = $(".task-progress-bar[data-task-id=" + taskID + "]");
                        if ($taskProgressBar.length) {
                            if (data.value >= 100) {
                                location.reload();
                            } else {
                                $taskProgressBar.css('width', data.value + '%');
                                $taskProgressBar.attr('aria-valuenow', data.value);
                                $taskProgressBar.find(".task-progress-bar-val").text(Math.floor(data.value) + " завершено");
                            }
                        } else {
                            console.log($taskProgressBar);
                        }

                    }, "JSON");
                } catch (err) {
                    console.log(err);
                }
            }, 10000);
        }


        $("#showListForm").click(function () {
            $(this).hide();
            $("#listForm").fadeIn();
            $("#hideListForm").show();
        });

        $("#hideListForm").click(function () {
            $(this).hide();
            $("#listForm").fadeOut();
            $("#showListForm").show();
        });

        $("#showTaskForm").click(function () {
            $(this).hide();
            $("#taskForm").fadeIn();
            $("#hideTaskForm").show();
        });

        $("#hideTaskForm").click(function () {
            $(this).hide();
            $("#taskForm").fadeOut();
            $("#showTaskForm").show();
        });

        if (location.hash == "#add") {
            if ($("#listForm").length) {
                $("#listForm").fadeIn();
                $("#showListForm").hide();
                $("#hideListForm").show();
            }

            if ($("#taskForm").length) {
                $("#taskForm").fadeIn();
                $("#showTaskForm").hide();
                $("#hideTaskForm").show();
            }
        }

        $("#taskMessage").keyup(updateTaskPlayURL);
        $("#taskSpeaker, #taskEmotion").change(updateTaskPlayURL);

        function updateTaskPlayURL() {
            if ($("#taskMessage").val()) {
                $("#taskPlay").attr('href', '/ajax.php?play=1&text=' + encodeURIComponent($("#taskMessage").val()) + '&speaker=' + $("#taskSpeaker option:selected").val() + '&emotion=' + $("#taskEmotion option:selected").val());
                $("#taskPlay").removeAttr('disabled');
                $("#taskStart").removeAttr('disabled');
            } else {
                $("#taskPlay").attr("disabled", "disabled");
                $("#taskStart").attr("disabled", "disabled");
            }
        }

        if ($(".phone-input").length) {
            $('.phone-input').on('keyup', function () {
                $(this).val('+' + $(this).val().replace(/\D/g, ''));
            });
            $('.phone-input').on('focusout', function () {
                if ($(this).val().replace(/\D/g, '') == '') {
                    $(this).val('');
                }
            });
            $('.phone-input').on('focusin', function () {
                if ($(this).val().replace(/\D/g, '') == '') {
                    $(this).val('+');
                }
            });
        }

        $(".audioinput").on("change", function () {
            $(this).parents('.form-ajax').submit();
        });


        $(document).on('submit', '.form-ajax', function (e) {
            e.preventDefault();

            var form = $(this), action = form.attr('action');
            var formData = new FormData(form[0]);

            //if (form.find('.form-ajax-loading').length) {
            //    form.hide();
            //    form.find('.form-ajax-loading').show();
            //}

            $.ajax({
                url: action,
                type: 'POST',
                data: formData,
                async: false,
                cache: false,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function (data) {
                    var reloadDelay = 0;

                    //form.find('.form-ajax-loading').hide();

                    //if (!data.result) {
                    //    form.show();
                    //}
                    if (data.message) {
                        var reloadDelay = 5000;
                        form.find('.form-ajax-message').html(data.message);
                        form.find('.form-ajax-message-wrapper').fadeIn();
                        if (!data.result) {
                            setTimeout(function () {
                                form.find('.form-ajax-message-wrapper').fadeOut();
                            }, reloadDelay);
                        }
                    }
                    if (data.redirect) {
                        setTimeout(function () {
                            window.location = data.redirect;
                        }, reloadDelay);
                    }
                    if (data.reload) {
                        setTimeout(function () {
                            location.reload();
                        }, reloadDelay);
                    }
                    if (data.reloadclear) {
                        setTimeout(function () {
                            window.location = window.location.href.split("?")[0];
                        }, reloadDelay);
                    }

                }
            });
        });


    };

    window.app = app;
}
(jQuery, window);


// NAVBAR MODULE
// =====================
+function ($, window) {
    'use strict';

    // Cache DOM
    var $body = app.$body,
        $navbar = app.$navbar;

    var navbar = {};

    navbar.init = function () {
        this.listenForEvents();
    };

    navbar.listenForEvents = function () {
        $(document)
            .on('click', '#navbar-search-open', openSearch)
            .on('click', '#search-close, .search-backdrop', closeSearch);
    };

    navbar.getAppliedTheme = function () {
        var appliedTheme = "", themes = app.settings.navbar.themes, theme;
        for (theme in themes) {
            if ($navbar.hasClass(themes[theme])) {
                appliedTheme = themes[theme];
                break;
            }
        }
        return appliedTheme;
    };

    navbar.getCurrentTheme = function () {
        return app.settings.navbar.theme;
    };

    navbar.setTheme = function (theme) {
        if (theme) app.settings.navbar.theme = theme;
    };

    navbar.applyTheme = function () {
        var appliedTheme = this.getAppliedTheme();
        var currentTheme = this.getCurrentTheme();

        $navbar.removeClass(appliedTheme)
            .addClass(currentTheme);

        $body.removeClass('theme-' + appliedTheme)
            .addClass('theme-' + currentTheme);
    };


    function openSearch(e) {
        e.preventDefault();
        e.stopPropagation();
        $navbar.append('<div class="search-backdrop"></div>');
        $('#navbar-search').addClass('open');
        $('.search-backdrop').addClass('open');
    }

    function closeSearch(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#navbar-search').removeClass('open');
        $('.search-backdrop').removeClass('open').remove();
    }

    window.app.navbar = navbar;
}(jQuery, window);


// SIDEBAR MODULE
// =====================
+function ($, window) {
    'use strict';
    // Cache DOM
    var $body = app.$body,
        $sidebar = app.$sidebar,
        $sidebarFold = $('#aside-fold'),
        $sidebarToggle = $('#aside-toggle');

    var sidebar = {};

    sidebar.init = function () {
        this.listenForEvents();
    };

    sidebar.listenForEvents = function () {
        var self = this;

        self.initScroll();
        self.toggleScroll();

        $body.on('mouseenter mouseleave', '.folded:not(.open) .has-submenu', function (e) {
            $(this).find('.submenu').toggle().end().siblings().find('.submenu').hide();
        });

        $body.on('click', '.submenu-toggle', function (e) {
            e.preventDefault();
            $(this).parent().toggleClass('open').find('.submenu').slideToggle(500).end().siblings().removeClass('open').find('.submenu').slideUp(500);
        });

        $sidebarFold.on('click', function (e) {
            e.preventDefault();
            self.fold();
            self.toggleScroll();
        });

        $sidebarToggle.on('click', self.open);

        $body.on('click', '.aside-backdrop', self.close);

        $(window).on('load', function (e) {
            var ww = $(window).width();
            if (ww < 992 && app.$sidebar.hasClass('folded')) {
                app.$sidebar.removeClass('folded');
                app.$body.removeClass('sb-folded');
                sidebar.toggleScroll();
            } else if (ww >= 992 && app.settings.sidebar.folded) {
                app.$sidebar.addClass('folded');
                app.$body.addClass('sb-folded');
                sidebar.toggleScroll();
            }
        });
    };

    sidebar.getAppliedTheme = function () {
        var appliedTheme = "", themes = app.settings.sidebar.themes, theme;
        for (theme in themes) {
            if ($sidebar.hasClass(themes[theme])) {
                appliedTheme = themes[theme];
                break;
            }
        }
        return appliedTheme;
    };

    sidebar.getCurrentTheme = function () {
        return app.settings.sidebar.theme;
    };

    sidebar.setTheme = function (theme) {
        if (theme) app.settings.sidebar.theme = theme;
    };

    sidebar.applyTheme = function () {
        $sidebar.removeClass(this.getAppliedTheme())
            .addClass(this.getCurrentTheme());
    };

    sidebar.fold = function () {
        $sidebarFold.toggleClass('is-active');
        $sidebar.toggleClass('folded');
        $body.toggleClass('sb-folded');
    };

    sidebar.open = function (e) {
        e.preventDefault();
        $sidebar.after('<div class="aside-backdrop"></div>');
        $sidebar.addClass('open');
        $sidebarToggle.addClass('is-active');
        $body.addClass('sb-open');
    };

    sidebar.close = function (e) {
        e.preventDefault();
        $sidebar.removeClass('open');

        $sidebarToggle.removeClass('is-active');
        $body
            .removeClass('sb-open')
            .find('.aside-backdrop')
            .remove();
    };

    sidebar.initScroll = function () {
        $('#aside-scroll-inner').slimScroll({
            height: 'auto',
            position: 'right',
            size: "5px",
            color: '#98a6ad',
            wheelStep: 5
        });
    };

    sidebar.toggleScroll = function () {
        var $scrollContainer = $('#aside-scroll-inner');
        if ($body.hasClass("sb-folded")) {
            $scrollContainer.css("overflow", "inherit").parent().css("overflow", "inherit");
            $scrollContainer.siblings(".slimScrollBar").css("visibility", "hidden");
        } else {
            $scrollContainer.css("overflow", "hidden").parent().css("overflow", "hidden");
            $scrollContainer.siblings(".slimScrollBar").css("visibility", "visible");
        }
    };

    window.app.sidebar = sidebar;
}(jQuery, window);


// AUDIO RECORDER MODULE
// =====================
+function ($, window) {
    'use strict';


    var recorder = {};

    recorder.audioContext = null;
    recorder.audioInput = null;
    recorder.realAudioInput = null;
    recorder.inputPoint = null;
    recorder.audioRecorder = null;
    recorder.rafID = null;
    recorder.analyserContext = null;
    recorder.canvasWidth = 0;
    recorder.canvasHeight = 0;
    recorder.maxAudioDuration = 5; //sec
    recorder.URL = window.URL || window.webkitURL;

    recorder.$currentAudioModal = null;
    recorder.$currentAnalyser = null;
    recorder.$startRecordingButton = null;
    recorder.$stopRecordingButton = null;
    recorder.$cancelRecordingButton = null;


    recorder.uninitRecorder = function () {
        recorder.audioContext = null;
        recorder.audioInput = null;
        recorder.realAudioInput = null;
        recorder.inputPoint = null;
        recorder.audioRecorder = null;
        recorder.rafID = null;
        recorder.analyserContext = null;
        recorder.canvasWidth = 0;
        recorder.canvasHeight = 0;
        recorder.URL = window.URL || window.webkitURL;

        recorder.$currentAudioModal = null;
        recorder.$currentAnalyser = null;
        recorder.$startRecordingButton = null;
        recorder.$stopRecordingButton = null;
        recorder.$cancelRecordingButton = null;
    };

    recorder.initRecorder = function ($modal) {
        //alert($modal.html());

        if (!recorder.audioRecorder) {
            window.AudioContext = window.AudioContext || window.webkitAudioContext;
            recorder.audioContext = new AudioContext();

            recorder.$currentAudioModal = $modal;
            recorder.$currentAnalyser = $modal.find(".analyzer");
            recorder.$startRecordingButton = $modal.find(".recstart");
            recorder.$stopRecordingButton = $modal.find(".recstop");
            recorder.$cancelRecordingButton = $modal.find(".reccancel");

            if (!navigator.getUserMedia)
                navigator.getUserMedia = navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
            if (!navigator.cancelAnimationFrame)
                navigator.cancelAnimationFrame = navigator.webkitCancelAnimationFrame || navigator.mozCancelAnimationFrame;
            if (!navigator.requestAnimationFrame)
                navigator.requestAnimationFrame = navigator.webkitRequestAnimationFrame || navigator.mozRequestAnimationFrame;

            navigator.getUserMedia(
                {
                    "audio": true
                }, recorder.gotStream, function (e) {
                    alert("Ошибка при получении источника входящего аудиопотока. \n" + e);
                    console.log(e);
                });
        }
    };


    recorder.gotStream = function (stream) {
        recorder.inputPoint = recorder.audioContext.createGain();

        // Create an AudioNode from the stream.
        recorder.realAudioInput = recorder.audioContext.createMediaStreamSource(stream);
        recorder.audioInput = recorder.realAudioInput;
        recorder.audioInput.connect(recorder.inputPoint);

        recorder.analyserNode = recorder.audioContext.createAnalyser();
        recorder.analyserNode.fftSize = 2048;
        recorder.inputPoint.connect(recorder.analyserNode);

        recorder.zeroGain = recorder.audioContext.createGain();
        recorder.zeroGain.gain.value = 0.0;
        recorder.inputPoint.connect(recorder.zeroGain);
        recorder.zeroGain.connect(recorder.audioContext.destination);

        recorder.audioRecorder = new WebAudioRecorder(recorder.inputPoint, {
            encoding: 'mp3',
            workerDir: "assets/js/rec/",     // must end with slash
            options: {timeLimit: recorder.maxAudioDuration, mp3: {bitRate: 320}, bufferSize: 4096}
        });
        recorder.audioRecorder.onTimeout = function (rec) {
            recorder.stopRecording();
        };
        recorder.audioRecorder.onComplete = function (rec, blob) {
            console.log('complete');
            recorder.clearAudioList();
            recorder.addAudioToList(recorder.URL.createObjectURL(blob));
        };
        recorder.audioRecorder.onError = function (rec, message) {
            console.log('Recorder error: ' + message);
            alert(message);
        };

        recorder.updateAnalysers();
    };


    recorder.updateAnalysers = function () {

        if (!recorder.$currentAnalyser) {
            return;
        }

        if (!recorder.analyserContext) {
            recorder.canvasWidth = recorder.$currentAnalyser[0].width;
            recorder.canvasHeight = recorder.$currentAnalyser[0].height;
            recorder.analyserContext = recorder.$currentAnalyser[0].getContext('2d');
        }

        var SPACING = 3;
        var BAR_WIDTH = 2;
        var numBars = Math.round(recorder.canvasWidth / SPACING);
        var freqByteData = new Uint8Array(recorder.analyserNode.frequencyBinCount);

        recorder.analyserNode.getByteFrequencyData(freqByteData);

        recorder.analyserContext.clearRect(0, 0, recorder.canvasWidth, recorder.canvasHeight);
        recorder.analyserContext.fillStyle = '#F6D565';
        recorder.analyserContext.lineCap = 'round';
        var multiplier = recorder.analyserNode.frequencyBinCount / numBars;

        // Draw rectangle for each frequency bin.
        for (var i = 0; i < numBars; ++i) {
            var magnitude = 0;
            var offset = Math.floor(i * multiplier);
            // gotta sum/average the block, or we miss narrow-bandwidth spikes
            for (var j = 0; j < multiplier; j++)
                magnitude += freqByteData[offset + j];
            magnitude = magnitude / multiplier;
            var magnitude2 = freqByteData[i * multiplier];
            recorder.analyserContext.fillStyle = "hsl( " + Math.round((i * 360) / numBars) + ", 100%, 50%)";
            recorder.analyserContext.fillRect(i * SPACING, recorder.canvasHeight, BAR_WIDTH, -magnitude);
        }
        recorder.rafID = window.requestAnimationFrame(recorder.updateAnalysers);
    };

    recorder.cancelRecording = function () {
        if (recorder.audioRecorder && recorder.audioRecorder.isRecording()) {
            recorder.audioRecorder.cancelRecording();
            console.log('reccancel');
            recorder.$startRecordingButton.show();
            recorder.$stopRecordingButton.hide();
            recorder.$cancelRecordingButton.hide();
        }
    };

    recorder.startRecording = function () {
        if (recorder.audioRecorder) {
            recorder.audioRecorder.startRecording();
            console.log('recstart');
            recorder.$startRecordingButton.hide();
            recorder.$stopRecordingButton.show();
            recorder.$cancelRecordingButton.show();
        }

    };

    recorder.stopRecording = function () {
        if (recorder.audioRecorder && recorder.audioRecorder.isRecording()) {
            recorder.audioRecorder.finishRecording();
            console.log('recend');
            recorder.$startRecordingButton.show();
            recorder.$stopRecordingButton.hide();
            recorder.$cancelRecordingButton.hide();
        }
    };


    /**
     * @param filename URLobject OR filename
     */
    recorder.addAudioToList = function (filename) {
        try {
            if (filename.indexOf('blob:') + 1) {
                var html = "<div class='rec' record='" + filename + "'><audio controls src='" + filename + "' type='audio/mpeg'></audio><a class='btn btn-default btn-sm' href='" + filename + "' download='record.mp3'>Скачать</a><a class='btn btn-default btn-sm' onclick='app.recorder.clearAudioList();'>Удалить</a></div>";
            } else {
                var file = app.taskConstructor.uploadFolder + filename;
                var html = "<div class='rec' record='" + filename + "'><audio controls src='" + file + "' type='audio/mpeg'></audio><a class='btn btn-default btn-sm' href='" + file + "' download='" + filename + "'>Скачать</a><a class='btn btn-default btn-sm' onclick='app.recorder.clearAudioList();'>Удалить</a></div>";
            }

            recorder.$currentAudioModal.find('.reclist').html(html);

            setTimeout(function () {
                if (recorder.$currentAudioModal.find('.reclist audio').length && recorder.$currentAudioModal.find('.reclist audio')[0].duration > app.recorder.maxAudioDuration) {
                    alert("Длительность прикрепленного аудиофайла превышает лимит " + app.recorder.maxAudioDuration + " секунд.");
                    recorder.clearAudioList();
                }
            }, 500);

        } catch (e) {
            alert(e);
        }

    };

    recorder.clearAudioList = function () {


        recorder.$currentAudioModal.find('.rec').each(function (i) {

            if ($(this).attr('record')) {
                if ($(this).attr('record').indexOf('blob:') + 1) {
                    recorder.URL.revokeObjectURL($(this).attr('record'));
                }

                $(this).remove();

            }

        });

    };

    window.app.recorder = recorder;
}
(jQuery, window);


// TASK CONSTRUCTOR
// =====================
+function ($, window) {
    'use strict';

    var taskConstructor = {};

    taskConstructor.maxScripts = 5;
    taskConstructor.task = {};
    taskConstructor.filepath = '/';

    taskConstructor.init = function () {

        if ($("#taskForm").length) {

            $("#taskForm .add-task-script").click(function () {
                taskConstructor.cloneScript();
            });

            $('#taskForm [name=title]').val(taskConstructor.task.title);
            $('#taskForm [name=title]').on('change', function () {
                taskConstructor.task.title = $(this).val();
            });

            $('#taskForm input:text[name=date_start]').val(taskConstructor.task.date_start ? taskConstructor.task.date_start : '');
            $('#taskForm input:text[name=date_start]').on('dp.change', function () {
                taskConstructor.task.date_start = $(this).val();
            });
            $("#taskForm input:checkbox[name=date_start]").change(function () {
                if (this.checked) {
                    $("#taskForm input:text[name=date_start]").attr("disabled", "disabled");
                    taskConstructor.task.date_start = $(this).val();
                } else {
                    $("#taskForm input:text[name=date_start]").removeAttr("disabled");
                    taskConstructor.task.date_start = $("#taskForm input:text[name=date_start]").val();
                }
            });

            $('#taskForm input:text[name=date_end]').val(taskConstructor.task.date_end ? taskConstructor.task.date_end : '');
            $('#taskForm input:text[name=date_end]').on('dp.change', function () {
                taskConstructor.task.date_end = $(this).val();
            });
            $("#taskForm input:checkbox[name=date_end]").change(function () {
                if (this.checked) {
                    $("#taskForm input:text[name=date_end]").attr("disabled", "disabled");
                    taskConstructor.task.date_end = $(this).val();
                } else {
                    $("#taskForm input:text[name=date_end]").removeAttr("disabled");
                    taskConstructor.task.date_end = $("#taskForm input:text[name=date_end]").val();
                }
            });

            $('#taskForm [name=days]').val(taskConstructor.task.days).trigger("change");
            $('#taskForm [name=days]').on('change', function () {
                taskConstructor.task.days = $(this).val();
            });

            $('#taskForm [name=hours]').val(taskConstructor.task.hours).trigger("change");
            $('#taskForm [name=hours]').on('change', function () {
                taskConstructor.task.hours = $(this).val();
            });


            $("#taskForm [name=robo_voice]").change(function () {
                taskConstructor.task.robo_voice = $(this).prop("checked") ? 1 : 0;
                taskConstructor.refreshButtons();
                taskConstructor.refreshCalculator();
            });

            $("#taskForm [name=voice_action]").change(function () {
                taskConstructor.task.voice_action = $(this).prop("checked") ? 1 : 0;
                taskConstructor.refreshButtons();
                taskConstructor.refreshCalculator();
            });


            $('#taskForm [name=list_id]').val(taskConstructor.task.list_id).trigger("change");
            $('#taskForm [name=list_id]').on('change', function () {
                taskConstructor.task.list_id = $(this).val();
            });

            $('#taskForm [name=phone_id]').val(taskConstructor.task.phone_id).trigger("change");
            $('#taskForm [name=phone_id]').on('change', function () {
                taskConstructor.task.phone_id = $(this).val();
            });

            taskConstructor.refreshCalculator();

            if (taskConstructor.task && taskConstructor.task.scripts) {
                taskConstructor.task.voice_action = parseInt(taskConstructor.task.voice_action);
                taskConstructor.task.robo_voice = parseInt(taskConstructor.task.robo_voice);


                if (taskConstructor.task.robo_voice) {
                    $('#taskForm [name=robo_voice]').prop('checked', true);
                    $('#taskForm [name=robo_voice]').siblings('.switchery').find('small').css('left', '20px');
                } else {
                    $('#taskForm [name=robo_voice]').prop('checked', false);
                    $('#taskForm [name=robo_voice]').siblings('.switchery').find('small').css('left', '0px');
                }

                if (taskConstructor.task.voice_action) {
                    $('#taskForm [name=voice_action]').prop('checked', true);
                    $('#taskForm [name=voice_action]').siblings('.switchery').find('small').css('left', '20px');
                } else {
                    $('#taskForm [name=voice_action]').prop('checked', false);
                    $('#taskForm [name=voice_action]').siblings('.switchery').find('small').css('left', '0px');
                }

                $.each(taskConstructor.task.scripts, function (key, script) {
                    if (key != 1) {
                        taskConstructor.cloneScript();
                    }

                    if (script.voice_actions) {

                        var currentVoiceActionButton = 0;
                        $.each(script.voice_actions, function (voiceActionKey, voiceActionData) {
                            voiceActionKey = voiceActionKey.replace(/[^a-zа-яёїі]/gmi, '');
                            if (voiceActionKey) {
                                var $voiceButton = $('[data-task-script-id="' + key + '"]').find('.task-script-voice-actions').find('.btn:eq(' + currentVoiceActionButton + ')');
                                if ($voiceButton) {
                                    $voiceButton.attr('data-action-id', voiceActionKey);
                                    $voiceButton.text(voiceActionKey);
                                }
                                currentVoiceActionButton++;

                            }
                        });
                    }


                });
                taskConstructor.refreshButtons();
                taskConstructor.refreshCalculator();
            } else {
                taskConstructor.task = {};
                taskConstructor.task.scripts = {};
            }

        }


    };

    taskConstructor.cloneScript = function () {
        var $clone = $("#task-scripts-container").find(".task-script").first().clone();

        $clone.appendTo("#task-scripts-container");

        $clone.attr("data-task-script-id", $(".task-script").length);
        $clone.find(".task-script-id").html($(".task-script").length);
        $clone.find('.btn.hl').removeClass('hl');

        $("#task-scripts-container [data-action-type=greeting]").hide();
        $("#task-scripts-container [data-action-type=goodbye]").hide();
        $("#task-scripts-container .task-script-goodbye-cap").show();


        $("#task-scripts-container [data-action-type=greeting]").first().show();
        $("#task-scripts-container [data-action-type=goodbye]").last().show();
        $("#task-scripts-container .task-script-goodbye-cap").last().hide();


        if ($(".task-script").length >= taskConstructor.maxScripts) {
            $(".add-task-script").hide();
            return false;
        } else {
            $(".add-task-script").show();
        }
    };

    taskConstructor.refreshCalculator = function () {
        if ($('#calculator').length && taskConstructor.task) {
            taskConstructor.task.get_calculator = $('#calculator').attr('data-calculator');

            $.ajax({
                url: '/ajax.php',
                type: 'POST',
                data: taskConstructor.task,
                dataType: 'json',
                success: function (data) {
                    $('#taskCalculator').html(data.message);
                }
            });

            delete taskConstructor.task.get_calculator;
        }


    };

    taskConstructor.refreshButtons = function () {
         $('.btn.hl').removeClass('hl');

        if (taskConstructor.task.voice_action) {
            $('.task-script-voice-actions').show();
            $('.task-script-button-actions').hide();

            $('.task-script-phone-label h6:eq(0)').hide();
            $('.task-script-phone-label h6:eq(1)').show();

        } else {
            $('.task-script-voice-actions').hide();
            $('.task-script-button-actions').show();

            $('.task-script-phone-label h6:eq(1)').hide();
            $('.task-script-phone-label h6:eq(0)').show();
        }

        $.each(taskConstructor.task.scripts, function (key, script_data) {

            var $script = $('[data-task-script-id=' + (key) + ']');
            if (taskConstructor.task.robo_voice) {
                if (script_data.greeting) {
                    $script.find('[data-action-type=greeting]').addClass('hl');
                }
                if (script_data.message) {
                    $script.find('[data-action-type=message]').addClass('hl');
                }
                if (script_data.goodbye) {
                    $script.find('[data-action-type=goodbye]').addClass('hl');
                }

            } else {
                if (script_data.greeting_mp3) {
                    $script.find('[data-action-type=greeting]').addClass('hl');
                }
                if (script_data.message_mp3) {
                    $script.find('[data-action-type=message]').addClass('hl');
                }
                if (script_data.goodbye_mp3) {
                    $script.find('[data-action-type=goodbye]').addClass('hl');
                }
            }

            if (taskConstructor.task.voice_action) {
                if (script_data.voice_actions) {

                    $.each(script_data.voice_actions, function (voiceActionKey, voiceActionData) {
                        if (voiceActionKey && voiceActionData) {
                            if (!jQuery.isEmptyObject(voiceActionData['action']) || !jQuery.isEmptyObject(voiceActionData['after_action'])) {
                                $script.find('.task-script-voice-actions .btn[data-action-id=' + voiceActionKey + ']').addClass("hl");
                            }
                        }

                    });

                }

            } else {
                if (script_data.button_actions) {
                    $.each(script_data.button_actions, function (buttonActionKey, buttonActionData) {
                        if (buttonActionKey && buttonActionData) {
                            if (!jQuery.isEmptyObject(buttonActionData['action']) || !jQuery.isEmptyObject(buttonActionData['after_action'])) {
                                $script.find('.task-script-button-actions .btn[data-action-id=' + buttonActionKey + ']').addClass("hl");
                            }
                        }

                    });

                }
            }


        });


    };

    taskConstructor.modalOpen = function (el) {
        var $btn = $(el);
        var scriptID = parseInt($btn.parents('.task-script').attr('data-task-script-id'));
        var scriptAction = $btn.attr('data-action-type');
        var scriptActionID = $btn.attr('data-action-id');
        var $modal = null;
        var title = '';


        if (taskConstructor.task.robo_voice && (scriptAction == 'greeting' || scriptAction == 'message' || scriptAction == 'goodbye')) {
            $modal = $('#modalText');
            var title = 'Текст для озвучки ';
            title += (scriptAction == 'greeting') ? 'приветствия' : ( scriptAction == 'message' ? 'основного сообщения' : 'прощания' );
            $modal.find('.modal-title').text(title);

        } else if (!taskConstructor.task.robo_voice && (scriptAction == 'greeting' || scriptAction == 'message' || scriptAction == 'goodbye')) {
            scriptAction += '_mp3';
            $modal = $('#modalAudio');
            var title = 'Аудиозапись ';
            title += (scriptAction == 'greeting') ? 'приветствия' : ( scriptAction == 'message_mp3' ? 'основного сообщения' : 'прощания' );
            $modal.find('.modal-title').text(title);

        } else if (scriptAction.indexOf('_actions') + 1) {
            $modal = taskConstructor.task.voice_action ? $('#modalVoiceActions') : $('#modalButtonActions');

        }

        if ($modal) {
            $modal.on('show.bs.modal', function () {
                if ($modal.hasClass('audiomodal')) {
                    app.recorder.initRecorder($modal);
                }
                app.taskConstructor.loadModalData($btn, $modal, scriptID, scriptAction, scriptActionID);
            });

            $modal.on('hide.bs.modal', function () {
                app.recorder.cancelRecording();
                app.taskConstructor.saveModalData($btn, $modal, scriptID, scriptAction, scriptActionID);
                app.taskConstructor.refreshButtons();
                app.taskConstructor.refreshCalculator();
            });

            $modal.on('hidden.bs.modal', function (e) {
                $(e.currentTarget).unbind();
            });

            $modal.modal('show');
        }

    };

    taskConstructor.addAudioFile = function (el) {
        if (el.files[0]) {
            app.recorder.addAudioToList(app.recorder.URL.createObjectURL(el.files[0]), 'mp3');
        }
    };

    taskConstructor.loadModalData = function ($btn, $modal, scriptID, scriptAction, scriptActionID) {

        if (taskConstructor.task.scripts[scriptID]) {

            if (!taskConstructor.task.scripts[scriptID][scriptAction]) {
                taskConstructor.task.scripts[scriptID][scriptAction] = '';
            }

            if (scriptAction == 'greeting' || scriptAction == 'message' || scriptAction == 'goodbye') {
                $modal.find('[name=message]').val(taskConstructor.task.scripts[scriptID][scriptAction]);
                $modal.find('[name=speaker]').val(taskConstructor.task.speaker);

            } else if (scriptAction == 'greeting_mp3' || scriptAction == 'message_mp3' || scriptAction == 'goodbye_mp3') {
                $modal.find('.reclist').html('');
                if (taskConstructor.task.scripts[scriptID][scriptAction]) {
                    app.recorder.addAudioToList(taskConstructor.task.scripts[scriptID][scriptAction]);
                }

            } else if (scriptAction == 'button_actions' || scriptAction == 'voice_actions') {

                $modal.find('select[name=goto]').html('');
                for (var i = 1; i <= $(".task-script").length; i++) {
                    $modal.find('select[name=goto]').append($('<option>', {value: i}).text('Скрипт#' + i));
                }

                $modal.find('input:text, select').val('');
                $modal.find('input[name="action"]:first').prop('checked', true);
                $modal.find('input[name="after_action"]:first').prop('checked', true);

                if (scriptAction == 'voice_actions') {
                    $modal.find('input[name="keyword"]').val(scriptActionID);
                }

                if (taskConstructor.task.scripts[scriptID][scriptAction][scriptActionID]) {
                    var action = taskConstructor.task.scripts[scriptID][scriptAction][scriptActionID]['action'];
                    if (action && Object.keys(action)[0]) {
                        var actionName = Object.keys(action)[0];
                        var actionVal = taskConstructor.task.scripts[scriptID][scriptAction][scriptActionID]['action'][actionName];
                        $modal.find('[name="action"][value="' + actionName + '"]').prop("checked", true);
                        $modal.find('[name="' + actionName + '"]').val(actionVal);
                    }

                    var afterAction = taskConstructor.task.scripts[scriptID][scriptAction][scriptActionID]['after_action'];
                    if (afterAction && Object.keys(afterAction)[0]) {
                        var afterActionName = Object.keys(afterAction)[0];
                        var afterActionVal = taskConstructor.task.scripts[scriptID][scriptAction][scriptActionID]['after_action'][afterActionName];
                        $modal.find('[name="after_action"][value="' + afterActionName + '"]').prop("checked", true);
                        $modal.find('[name="' + afterActionName + '"]').val(afterActionVal);
                    }

                }

            }
        }
    };

    taskConstructor.saveModalData = function ($btn, $modal, scriptID, scriptAction, scriptActionID) {
        if (!taskConstructor.task.scripts[scriptID]) {
            taskConstructor.task.scripts[scriptID] = {};
            taskConstructor.task.scripts[scriptID][scriptAction] = '';
        }

        if (scriptAction == 'greeting' || scriptAction == 'message' || scriptAction == 'goodbye') {
            taskConstructor.task.scripts[scriptID][scriptAction] = $modal.find('[name=message]').val();
            if (taskConstructor.task.robo_voice) {
                taskConstructor.task.speaker = $modal.find('[name=speaker]').val();
            }

        } else if (scriptAction == 'greeting_mp3' || scriptAction == 'message_mp3' || scriptAction == 'goodbye_mp3') {
            taskConstructor.task.scripts[scriptID][scriptAction] = '';
            if ($modal.find('.rec').length) {
                taskConstructor.task.scripts[scriptID][scriptAction] = $modal.find('.rec').attr('record');
                taskConstructor.task.scripts[scriptID][scriptAction + '_duration'] = $modal.find('.rec audio')[0].duration;
            }

        } else if (scriptAction == 'button_actions' || scriptAction == 'voice_actions') {

            if (scriptAction == 'voice_actions') {
                var $keyword = $modal.find('input[name="keyword"]');
                var keyword = $keyword.val().replace(/[^a-zа-яёїі]/gmi, '');
                $keyword.val(keyword);
                $btn.attr('data-action-id', keyword);
                $btn.text(keyword);
                scriptActionID = keyword;
            }

            if (!taskConstructor.task.scripts[scriptID][scriptAction]) {
                taskConstructor.task.scripts[scriptID][scriptAction] = {};
            }
            taskConstructor.task.scripts[scriptID][scriptAction][scriptActionID] = {};
            taskConstructor.task.scripts[scriptID][scriptAction][scriptActionID]['action'] = {};
            taskConstructor.task.scripts[scriptID][scriptAction][scriptActionID]['after_action'] = {};

            var scriptActionName = $modal.find('input:radio[name="action"]:checked').val();
            if (scriptActionName) {
                var scriptActionVal = $modal.find('[name="' + scriptActionName + '"]').val();
                taskConstructor.task.scripts[scriptID][scriptAction][scriptActionID]['action'][scriptActionName] = scriptActionVal ? scriptActionVal : 1;
            }

            var scriptAfterActionName = $modal.find('input:radio[name="after_action"]:checked').val();
            if (scriptAfterActionName) {
                var scriptAfterActionVal = $modal.find('[name="' + scriptAfterActionName + '"]').val();
                taskConstructor.task.scripts[scriptID][scriptAction][scriptActionID]['after_action'][scriptAfterActionName] = scriptAfterActionVal ? scriptAfterActionVal : 1;
            }

        }

    };


    taskConstructor.uploadAudioQueue = function (callback) {

        var queue = taskConstructor.audioQueue.slice();
        $.each(queue, function (i, audio) {
            taskConstructor.task.scripts[audio.scriptID][audio.actionID] = '';

            var xhr = new XMLHttpRequest();
            xhr.responseType = 'blob';
            xhr.open('GET', audio.url, true);
            xhr.onload = function (e) {
                if (this.status != 200) {
                    taskConstructor.errors.push('Ошибка при сохранении файла в скрипте:' + audio.scriptID + ", аудиодорожка:'" + audio.actionID);
                    console.log(this.statusText);

                    var index = taskConstructor.audioQueue.indexOf(audio);
                    if (index > -1) {
                        taskConstructor.audioQueue.splice(index, 1);
                    }

                    callback();
                } else {
                    var blob = this.response;
                    var fdata = new FormData();
                    fdata.append('mp3', blob);
                    fdata.append('task_audio_upload', 1);
                    fdata.append('task_script_id', audio.scriptID);
                    fdata.append('task_action_id', audio.actionID);

                    $.ajax({
                        url: "/ajax.php",
                        type: 'POST',
                        data: fdata,
                        cache: false,
                        contentType: false,
                        processData: false,
                        dataType: 'json',
                        success: function (data) {
                            if (data.filename) {
                                taskConstructor.task.scripts[audio.scriptID][audio.actionID] = data.filename;
                            } else {
                                taskConstructor.errors.push(data.message);
                                console.log(data.message);
                            }

                            var index = taskConstructor.audioQueue.indexOf(audio);
                            if (index > -1) {
                                taskConstructor.audioQueue.splice(index, 1);
                            }

                            callback();
                        }
                    });
                }

            };
            xhr.send();

        });

    };


    taskConstructor.prepareSaveTask = function (schedule, type) {
        taskConstructor.audioQueue = [];
        taskConstructor.errors = [];

        if (type == 'responder') {
            taskConstructor.task['responder_save'] = 1;
        } else {
            taskConstructor.task['task_save'] = 1;
        }

        if (schedule) {
            taskConstructor.task['schedule'] = 1;
        }

        //Load blob on server
        $.each(taskConstructor.task.scripts, function (i, script) {
            if (script.greeting_mp3 && script.greeting_mp3.indexOf('blob:') + 1) {
                taskConstructor.audioQueue.push({
                    url: script.greeting_mp3,
                    scriptID: script.script_id,
                    actionID: 'greeting_mp3'
                });
            }
            if (script.message_mp3 && script.message_mp3.indexOf('blob:') + 1) {
                taskConstructor.audioQueue.push({url: script.message_mp3, scriptID: i, actionID: 'message_mp3'});
            }
            if (script.goodbye_mp3 && script.goodbye_mp3.indexOf('blob:') + 1) {
                taskConstructor.audioQueue.push({url: script.goodbye_mp3, scriptID: i, actionID: 'goodbye_mp3'});
            }

        });

        if (taskConstructor.audioQueue.length) {
            taskConstructor.uploadAudioQueue(taskConstructor.saveTask);
        } else {
            taskConstructor.saveTask();
        }

    };

    taskConstructor.saveTask = function () {
        if (!taskConstructor.audioQueue.length) {
            taskConstructor.refreshButtons();
            taskConstructor.refreshCalculator();


            $.ajax({
                url: '/ajax.php',
                type: 'POST',
                data: taskConstructor.task,
                dataType: 'json',
                success: function (data) {
                    //Если есть сообщение или ошибки - показать.
                    $('.form-ajax-message').html('');
                    if (data.message || taskConstructor.errors.length) {
                        $.each(taskConstructor.errors, function (i, error) {
                            $('#taskForm .form-ajax-message').append('<p>' + error + '</p>');
                        });
                        $('#taskForm .form-ajax-message').append('<p>' + data.message + '</p>');
                        $('#taskForm .form-ajax-message-wrapper').fadeIn();
                    }

                    //Если успех и нет ошибок - перезагрузить по таймауту.
                    if (data.success && !taskConstructor.errors.length) {
                        setTimeout(function () {
                            window.location.href = window.location.href.split("#")[0];
                        }, 5000);
                    }

                }
            });

            delete taskConstructor.task['responder_save'];
            delete taskConstructor.task['task_save'];
            delete taskConstructor.task['schedule'];

        }

    };


    window.app.taskConstructor = taskConstructor;

}(jQuery, window);


// initialize app
+function ($, window) {
    'use strict';
    window.app.init();
    window.app.navbar.init();
    window.app.sidebar.init();

}(jQuery, window);