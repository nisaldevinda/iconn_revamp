import React, { useEffect, useState } from 'react';
import { Bar } from '@ant-design/charts';
import { getAllEmployee } from '@/services/employee';
import moment from 'moment';

let config = {};

const AgeChart: React.FC = () => {

  const [chartData,setChartData] = useState<{}[]>([
    {
      age: '20-30',
      amount: 0,
    },
    {
      age: '30-40',
      amount: 0,
    },
    {
      age: '40-50',
      amount: 0,
    },
    {
      age: '50-60',
      amount: 0,
    }
  ]);

  useEffect(async () => {

    const data = await getAllEmployee();
    const birthdays = data.data.map((item: { dateOfBirth: moment.MomentInput; }) => {
      const age = moment().diff(moment(item.dateOfBirth, "DD MMM YYYY"), 'years');
      return age;
    });
    let twenties = 0;
    let thirties = 0;
    let fourties = 0;
    let fifties = 0;

    await birthdays.map((age: number) => {
      if (age > 20 && age < 30) twenties++;
      else if (age > 30 && age < 40) thirties++;
      else if (age > 40 && age < 50) fourties++;
      else fifties++;
    });

    setChartData(
      [
        {
          age: '20-30',
          amount: twenties,
        },
        {
          age: '30-40',
          amount: thirties,
        },
        {
          age: '40-50',
          amount: fourties,
        },
        {
          age: '50-60',
          amount: fifties,
        }
      ]
    );
  }, [])

  useEffect(() => {
    config = {
      xField: 'amount',
      yField: 'age'
    };
  },[chartData])

  return  <Bar height={200} width={340}data={chartData} {...config} />;


}

export default AgeChart; 