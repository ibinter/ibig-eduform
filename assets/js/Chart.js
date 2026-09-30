<canvas id="chartPlatforms"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartPlatforms'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels); ?>,
    datasets: [{
      label: 'Partages',
      data: <?= json_encode($values); ?>,
      backgroundColor: '#0b3c6d'
    }]
  }
});
</script>
