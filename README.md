# Traffic Prediction Experiment Platform

[Wireless and Mobile Networking Laboratary, National Tsing Hua University](http://wmnet.cs.nthu.edu.tw/index.html)
***

Code for data-centric traffic prediction experiment platform used in the following paper:
  - "Short-Term Traffic Prediction for EdgeComputing-Enhanced Autonomous and ConnectedCars" by Shun-Ren Yang, Yu-Ju Su, Yao-Yuan Chang, and Hui-Nien Hung
 
## Requirements

  - XAMPP with PHP 7.2.12
  - PECL statistics extension
  
## Installation

Download files to the following path
```sh
/Applications/XAMPP/htdocs/
```

## Traffic information base configuration
```mysql
CREATE DATABASE transportation;

CREATE TABLE `SensorID` (
  `ID` int(20) NOT NULL AUTO_INCREMENT,
  `avgSpeed` double NOT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `date_to_day` (
  `date` date NOT NULL,
  `day` int(2) NOT NULL,
  `holiday` tinyint(1) NOT NULL,
  PRIMARY KEY (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

## Getting started
1. Open the XAMPP
2. Start Apache and MySQL
3. Open the following URL in the browser
```sh
http://127.0.0.1/dashboard/Experiment-platform
```

## License

[Apache License 2.0](https://github.com/vdfjq369/Experiment-platform/blob/master/LICENSE)

----
