# Problems by Severity Widget with ECharts Support

This is an enhanced version of the Problems by Severity widget for Zabbix that adds support for ECharts visualization and tag-based grouping.

## New Features

### ECharts Visualization
- Added a new visualization option using ECharts library
- Users can choose between:
  - **Default View**: The standard Zabbix problems visualization
  - **ECharts View**: Custom visualization using ECharts library
- When ECharts view is selected, users can input their own ECharts configuration code to create custom visualizations

### Tag-based Grouping
The widget now supports three different ways to display problems:
- **Host Groups**: Group problems by host groups (default)
- **Totals**: Show total counts
- **Tags**: Group problems by their tags

### Configuration Options

#### View Type
- **Default**: Uses the standard Zabbix table visualization
- **ECharts**: Enables custom visualization using ECharts

#### Show Options
- **Host groups**: Display problems grouped by host groups
- **Totals**: Display total problem counts
- **Tags**: Display problems grouped by their tags

#### ECharts Code
When ECharts view is selected, you can input your own ECharts configuration code to create custom visualizations. The textarea becomes enabled and allows you to define your visualization using ECharts JSON configuration format.

## Installation

1. Copy the module files to your Zabbix modules directory:
```bash
cp -r problemsbysvmnz /usr/share/zabbix/modules/
```

2. Enable the module in Zabbix:
- Go to Administration → General → Modules
- Find "Problems by severity Monzphere" module
- Click on Enable

## Usage

1. Add the widget to your dashboard
2. Configure the widget settings:
   - Select your preferred view type (Default/ECharts)
   - Choose how to show the data (Host groups/Totals/Tags)
   - If using ECharts view, input your ECharts configuration code
3. Save the widget configuration

## ECharts Configuration Example

Here's a basic example of an ECharts configuration that creates a pie chart:

```javascript
{
    tooltip: {
        trigger: 'item'
    },
    legend: {
        orient: 'vertical',
        left: 'left'
    },
    series: [
        {
            name: 'Problems',
            type: 'pie',
            radius: '50%',
            data: [
                { value: severities[5], name: 'Disaster' },
                { value: severities[4], name: 'High' },
                { value: severities[3], name: 'Average' },
                { value: severities[2], name: 'Warning' },
                { value: severities[1], name: 'Information' },
                { value: severities[0], name: 'Not classified' }
            ],
            emphasis: {
                itemStyle: {
                    shadowBlur: 10,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                }
            }
        }
    ]
}
```

## Requirements

- Zabbix 6.4 or later
- Web browser with JavaScript enabled
- ECharts library (included in the module) 