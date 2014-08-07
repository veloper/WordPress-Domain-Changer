// if(document.getElementById('seconds')) {
//   window.setInterval(function() {
//     var seconds_elem = document.getElementById('seconds');
//     var bar_elem     = document.getElementById('bar');
//     var seconds      = parseInt(seconds_elem.innerHTML);
//     var percentage   = Math.round(seconds / (5 * 1000));
//     var bar_color    = '#00FF19';
//     if(percentage < 25) {
//         bar_color = 'red';
//     } else if (percentage < 75) {
//         bar_color = 'yellow';
//     }
//     if(seconds <= 0) window.location.reload();
//     bar_elem.style.width = percentage + '%';
//     bar_elem.style.backgroundColor = bar_color;
//     seconds_elem.innerHTML = --seconds;
//   }, 1000);
// }

if((inputs = document.getElementsByTagName('input')).length) {
  inputs[0].focus();
}


