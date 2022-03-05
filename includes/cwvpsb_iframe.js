var tag = document.createElement('script');
tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
var player;
function onYouTubePlayerAPIReady(event) {

  var iframe_player = document.querySelectorAll( ".iframe_player" );
  for (var i = 0; i < iframe_player.length; i++) {
	  var image = new Image();
	  image.src = "https://i3.ytimg.com/vi/"+ iframe_player[i].dataset.embed +"/hqdefault.jpg";
	  image.alt = "click to play the video";
	  image.addEventListener( "load", function() {
		 iframe_player[ i ].appendChild( image );
	  }( i ) );

    iframe_player[i].addEventListener( "click", function() {
      player = new YT.Player(this.id, {
        height: '',
        width: '',
        videoId: this.dataset.embed,
        events: {
          'onReady': onPlayerReady
		}
	  });

      function onPlayerReady(event) {
        event.target.playVideo();
	  }

      this.innerHTML = "";
	});  	  
  }
}