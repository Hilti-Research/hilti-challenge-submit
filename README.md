# SLAM Challenge Evaluation System

[![PHP Composer](https://github.com/Hilti-Research/hilti-challenge-submit/actions/workflows/php.yml/badge.svg)](https://github.com/Hilti-Research/hilti-challenge-submit/actions/workflows/php.yml)
[![Node.js Encore](https://github.com/Hilti-Research/hilti-challenge-submit/actions/workflows/node.js.yml/badge.svg)](https://github.com/Hilti-Research/hilti-challenge-submit/actions/workflows/node.js.yml)

This tool enables participants of the SLAM challenge to submit their solution. The solution is evaluated against the ground truth and scored. A report and the score are shown to the user. Further, a leaderboard shows the best submissions.

The symfony/PHP web application provides the user-facing functionality. AWS is used to evaluate the submissions.
