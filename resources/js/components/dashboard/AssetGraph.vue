<template>
    <div id="chart1"></div>
</template>

<script>

import {createChart} from "lightweight-charts";

export default {
    mounted() {
        const chart = createChart('chart1', { height: 350 });

        const lineSeries = chart.addAreaSeries({
            topColor: 'rgba(171, 71, 188, 0.56)',
            bottomColor: 'rgba(171, 71, 188, 0.04)',
            lineColor: 'rgba(171, 71, 188, 1)',
            lineWidth: 2,
        });

        const avgPriceLine = {
            price: window.avgPrice,
            color: '#ce6f28',
        };

        lineSeries.createPriceLine(avgPriceLine);
        lineSeries.setData(window.series);
    }
}
</script>
