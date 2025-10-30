<footer>
  <div class="container">
    <p>&copy; <?= date('Y') ?> AstroBite. All rights reserved.</p>
    <div class="footer-clock" id="utcClock" aria-label="UTC Clock"></div>
  </div>
</footer>
<script>
  (function() {
    function pad(n){return n<10?('0'+n):n}
    function tick(){
      try{
        var d=new Date();
        var y=d.getUTCFullYear();
        var mo=pad(d.getUTCMonth()+1);
        var da=pad(d.getUTCDate());
        var h=pad(d.getUTCHours());
        var m=pad(d.getUTCMinutes());
        var s=pad(d.getUTCSeconds());
        var el=document.getElementById('utcClock');
        if(el){el.textContent='UTC '+y+'-'+mo+'-'+da+' '+h+':'+m+':'+s}
      }catch(e){}
    }
    tick();
    setInterval(tick,1000);
  })();
</script>
</body>
</html>
