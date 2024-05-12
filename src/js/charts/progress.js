if (document.getElementById("js-progress-chart-total") !== null &&
  document.getElementById("js-progress-chart-weekly") !== null &&
  progressData !== null) {
  let x = [];
  let yAdded = [];
  let yTotal = [];

  for (const week in progressData) {
    x.push(progressData[week]["firstDay"]);
    yAdded.push(progressData[week]["recordsAdded"]);
    yTotal.push(progressData[week]["recordsTotal"]);
  }

  let yRangeMin = yTotal[0];
  let yRangeMax = yTotal[yTotal.length - 1];
  let yRangeDiff = yRangeMax - yRangeMin;
  yRangeMin = Math.max(yRangeMin - yRangeDiff * 0.05, 0);
  yRangeMax = yRangeMax + yRangeDiff * 0.05;

  let data = [{
    x: x,
    y: yTotal,
    fill: "tozeroy",
    hoveron: "points+fills",
    line: {
      shape: "hv"
    },
    name: "",
    type: "scatter"
  }];

  let layout = {
    font: {
      family: "Inter, sans-serif",
      size: 14
    },
    hovermode: "x",
    margin: {
      t: 32,
      r: 32,
      b: 48,
      l: 40
    },
    xaxis: {
      hoverformat: "Woche vom %d. %b %Y"
    },
    yaxis: {
      autorange: false, // Range needs to be overridden as fill=tozeroy seems to set rangemode=tozero automatically
      hoverformat: ",",
      range: [yRangeMin, yRangeMax]
    }
  };

  let config = {
    locale: "de",
    responsive: true
  };

  Plotly.newPlot("js-progress-chart-total", data, layout, config);

  data[0]["y"] = yAdded;
  data[0]["line"]["shape"] = "spline";
  delete (layout["yaxis"]["autorange"]);
  delete (layout["yaxis"]["range"]);

  Plotly.newPlot("js-progress-chart-weekly", data, layout, config);
}

if (document.getElementById("js-progress-chart-by-user") !== null) {
  let traces = [];

  for (const user in progressData["users"]) {
    let trace = {
      x: [],
      y: [],
      name: progressData["users"][user]["label"]
    };

    for (const week in progressData["weeks"]) {
      trace["x"].push(progressData["weeks"][week]["firstDay"]);

      if (week in progressData["users"][user]["weeks"]) {
        trace["y"].push(progressData["users"][user]["weeks"][week]);
      } else {
        trace["y"].push(0);
      }
    }

    traces.push(trace);
  }

  Plotly.newPlot("js-progress-chart-by-user", traces, {
      font: {
        family: "Inter, sans-serif",
        size: 14
      },
      height: 600,
      hovermode: "x",
      legend: {
        orientation: "h",
        x: 0.5,
        xanchor: "center"
      },
      margin: {
        t: 32,
        r: 32,
        b: 48,
        l: 40
      },
      xaxis: {
        hoverformat: "Woche vom %d. %b %Y"
      },
    },
    {
      locale: "de",
      responsive: true
    });
}
