<?php

# Load the ContributionScores extension to enable tracking and displaying of user contributions.
wfLoadExtension( 'ContributionScores' );

# Configure the time periods for contribution tracking.
# Each array entry specifies the time period (in days) and the number of top contributors to display.
$wgContribScoreReports = [
    [1, 10],    // Daily: Show the top 10 contributors for the past day.
    [7, 10],    // Weekly: Show the top 10 contributors for the past week.
    [14, 10],   // Biweekly: Show the top 10 contributors for the past two weeks.
    [30, 10],   // Monthly: Show the top 10 contributors for the past month.
    [90, 10],   // Quarterly: Show the top 10 contributors for the past three months.
    [365, 10],  // Yearly: Show the top 10 contributors for the past year.
    [0, 10]     // All-time: Show the top 10 contributors overall.
];

# Contribution scoring rules
$wgContribScoreIgnoreBots = true;
$wgContribScoreIgnoreBlockedUsers = true;    // Exclude blocked users from scoring to avoid highlighting disruptive accounts.
$wgContribScoresUseRealName = false;         // Use usernames instead of real names for contributor display.

# Cache settings for ContributionScores
$wgContribScoreDisableCache = true;          // Disable caching to ensure contribution scores are always up-to-date.
