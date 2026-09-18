<div id="Countdown" style="display:none;">
    <div id="CountdownLabel" class="countdown_element"></div>
    <div id="CountdownRemainingTime" class="countdown_element">
        <div class="countdown_part">
            <div id="CountdownDayValue" class="countdown_part_value"></div>
            <div class="countdown_part_label">{l s='jours' d='Shop.Theme.Customdtc'}</div>
        </div>
        :
        <div class="countdown_part">
            <div id="CountdownHourValue" class="countdown_part_value"></div>
            <div class="countdown_part_label">{l s='heures' d='Shop.Theme.Customdtc'}</div>
        </div>
        :
        <div class="countdown_part">
            <div id="CountdownMinuteValue" class="countdown_part_value"></div>
            <div class="countdown_part_label">{l s='minutes' d='Shop.Theme.Customdtc'}</div>
        </div>
        :
        <div class="countdown_part">
            <div id="CountdownSecondValue" class="countdown_part_value"></div>
            <div class="countdown_part_label">{l s='secondes' d='Shop.Theme.Customdtc'}</div>
        </div>
    </div>
    <a href="" id="CountdownCTA" class="countdown_element"></a>
</div>

<script type="text/javascript">
    (()=> {

        const createCountdown = (countdown) => {
            document.getElementById('Countdown').style.backgroundColor = countdown.bg_color;
            document.getElementById('CountdownLabel').innerHTML = countdown.label;
            document.getElementById('CountdownLabel').style.color = countdown.txt_color;
            document.getElementById('CountdownRemainingTime').style.color = countdown.txt_color;
            [].forEach.call(document.getElementsByClassName('countdown_part_label'), function (el) {
                el.style.color = countdown.txt_color;
            });
            [].forEach.call(document.getElementsByClassName('countdown_part_value'), function (el) {
                el.style.color = countdown.txt_color;
            });
            if(countdown.cta === '1') {
                document.getElementById('CountdownCTA').style.display = 'block';
                document.getElementById('CountdownCTA').style.backgroundColor = countdown.cta_bg_color;
                document.getElementById('CountdownCTA').style.color = countdown.cta_txt_color;
                document.getElementById('CountdownCTA').innerHTML = countdown.cta_label;
                document.getElementById('CountdownCTA').href = countdown.cta_link;
            }
        }

        const arr = {$countdowns|json_encode nofilter};

        const timestamp_current = new Date().getTime()
        let currentCountdown = ''
        const checkCountdown = () => {
            arr.forEach(countdown => {
                const timestamp_start = new Date(countdown.start_date).getTime();
                const timestamp_end = new Date(countdown.end_date).getTime();
                if(timestamp_start > timestamp_current || timestamp_end < timestamp_current) return
                currentCountdown = countdown
            })
            if(currentCountdown !== '') return 1
            else return 0
        }
        checkCountdown()

        const start_date = currentCountdown.start_date;
        const end_date = currentCountdown.end_date;

        const timestamp_start = new Date(start_date).getTime();

        if (isNaN(timestamp_start) || !timestamp_current || !timestamp_start || timestamp_start > timestamp_current) return

        const calculateRemainingTime = (countdown_endDate) => {
            const current_time = new Date().getTime();
            countdown_endDate = new Date(end_date).getTime();
            if (isNaN(countdown_endDate)) return false

            return parseInt((countdown_endDate - current_time) / 1000);
        }

        let remaining_time = calculateRemainingTime(end_date);

        if (!remaining_time || remaining_time <= 0) return

        document.getElementById('Countdown').style.display = 'flex';
        createCountdown(currentCountdown)

        const formatCountdown = () => {
            const days = parseInt(remaining_time / 86400);
            const hours = parseInt((remaining_time % 86400) / 3600);
            const minutes = parseInt((remaining_time % 3600) / 60);
            const seconds = parseInt(remaining_time % 60);

            return {
                days: days < 10 ? '0' + days : days,
                hours: hours < 10 ? '0' + hours : hours,
                minutes: minutes < 10 ? '0' + minutes : minutes,
                seconds: seconds < 10 ? '0' + seconds : seconds
            }
        }


        const updateCountdown = () => {
            const formated = formatCountdown();
            remaining_time = calculateRemainingTime(end_date) + 1;

            $('#CountdownDayValue').html(formated.days)
            $('#CountdownHourValue').html(formated.hours)
            $('#CountdownMinuteValue').html(formated.minutes)
            $('#CountdownSecondValue').html(formated.seconds)
            if(remaining_time < 0) {
                if (checkCountdown()) {
                    currentCountdown = ''
                    checkCountdown()

                    createCountdown(currentCountdown)
                    remaining_time = calculateRemainingTime(end_date)
                } else {
                    document.getElementById('Countdown').style.display = 'none';
                    clearInterval(refreshInterval)
                }
            }
        }
        const refreshInterval = setInterval(updateCountdown, 1000)
    })();
</script>
