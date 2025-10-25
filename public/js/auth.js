document.addEventListener('DOMContentLoaded', function () {
  if (window.sliderCaptcha) {
    sliderCaptcha({
      id: 'sliderCaptcha',
      width: 280,
      height: 155,
      sliderL: 42,
      sliderR: 9,
      offset: 5,
      loadingText: 'Loading...',
      failedText: 'Try again',
      barText: 'Slide to match the puzzle piece',
      repeatIcon: 'fa fa-redo',
      onSuccess: function () {
        const sliderInput = document.getElementById('slider_captcha');
        if (sliderInput) {
          sliderInput.value = 'verified';
        }
      }
    });
  }
});
