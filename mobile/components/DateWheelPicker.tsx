import React, { useState, useEffect, useMemo } from 'react';
import { View, StyleSheet, Dimensions } from 'react-native';
import WheelPicker from './WheelPicker';

interface DateWheelPickerProps {
  value: Date;
  onChange: (date: Date) => void;
  minimumDate?: Date;
  maximumDate?: Date;
}

const DateWheelPicker = ({ value, onChange, minimumDate, maximumDate }: DateWheelPickerProps) => {
  const [day, setDay] = useState(value.getDate());
  const [month, setMonth] = useState(value.getMonth());
  const [year, setYear] = useState(value.getFullYear());

  // Months names in French
  const months = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
  ];

  // Calculate days in month
  const getDaysInMonth = (m: number, y: number) => {
    return new Date(y, m + 1, 0).getDate();
  };

  // Generate years range
  const years = useMemo(() => {
    const currentYear = new Date().getFullYear();
    const minYear = minimumDate ? minimumDate.getFullYear() : currentYear - 100;
    const maxYear = maximumDate ? maximumDate.getFullYear() : currentYear + 10;
    const y = [];
    for (let i = minYear; i <= maxYear; i++) {
      y.push({ label: `${i}`, value: i });
    }
    return y;
  }, [minimumDate, maximumDate]);

  // Generate months
  const monthItems = useMemo(() => {
    return months.map((m, index) => ({ label: m, value: index }));
  }, []);

  // Generate days based on month/year
  const dayItems = useMemo(() => {
    const daysInMonth = getDaysInMonth(month, year);
    const d = [];
    for (let i = 1; i <= daysInMonth; i++) {
      d.push({ label: `${i}`, value: i });
    }
    return d;
  }, [month, year]);

  // Handle changes
  const handleDayChange = (d: number) => {
    setDay(d);
    updateDate(d, month, year);
  };

  const handleMonthChange = (m: number) => {
    setMonth(m);
    // Check if day is valid for new month
    const maxDays = getDaysInMonth(m, year);
    const newDay = day > maxDays ? maxDays : day;
    if (day > maxDays) setDay(newDay);
    updateDate(newDay, m, year);
  };

  const handleYearChange = (y: number) => {
    setYear(y);
    // Check if day is valid for new year (leap year)
    const maxDays = getDaysInMonth(month, y);
    const newDay = day > maxDays ? maxDays : day;
    if (day > maxDays) setDay(newDay);
    updateDate(newDay, month, y);
  };

  const updateDate = (d: number, m: number, y: number) => {
    const newDate = new Date(y, m, d);
    onChange(newDate);
  };

  // Sync internal state if value prop changes externally
  useEffect(() => {
    if (value) {
      setDay(value.getDate());
      setMonth(value.getMonth());
      setYear(value.getFullYear());
    }
  }, [value]);

  return (
    <View style={styles.container}>
      <View style={styles.pickerContainer}>
        {/* Day */}
        <View style={{ flex: 1, height: 250 }}>
          <WheelPicker
            items={dayItems}
            selectedValue={day}
            onValueChange={handleDayChange}
            width="100%"
            itemHeight={50}
          />
        </View>

        {/* Month */}
        <View style={{ flex: 1.5, height: 250 }}>
          <WheelPicker
            items={monthItems}
            selectedValue={month}
            onValueChange={handleMonthChange}
            width="100%"
            itemHeight={50}
          />
        </View>

        {/* Year */}
        <View style={{ flex: 1, height: 250 }}>
          <WheelPicker
            items={years}
            selectedValue={year}
            onValueChange={handleYearChange}
            width="100%"
            itemHeight={50}
          />
        </View>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: 'white',
    paddingVertical: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pickerContainer: {
    flexDirection: 'row',
    width: '100%',
    justifyContent: 'space-between',
    paddingHorizontal: 10,
  }
});

export default DateWheelPicker;
