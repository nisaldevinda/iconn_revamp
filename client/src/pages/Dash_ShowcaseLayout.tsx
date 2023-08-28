import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import _ from 'lodash';
import { Responsive, WidthProvider } from 'react-grid-layout';
import { getModel, Models, ModelType } from '@/services/model';
import { getDashboard } from '@/services/dashboard';
import CardLayout from '@/components/Dashboard/CardLayout';
import { Access, useAccess } from 'umi';
import { response } from 'express';
import { getPendingRequestCount } from '@/services/workflowServices';
import { getDocumentAcknowledgeCount } from '@/services/documentManager';

const ResponsiveReactGridLayout = WidthProvider(Responsive);

interface layoutProps {
  className?: string;
  rowHeight?: number;
  // onLayoutChange: function () { },
  cols?: { lg: 12; md: 10; sm: 6; xs: 4; xxs: 2 };
  initialLayout?: {
    x: number;
    y: number;
    w: number;
    h: number;
    i: string;
    static: boolean;
    data: any;
  }[];
  onLayoutChange?: (values: any) => any;
  // isResizable: boolean,
}

const Dash_ShowcaseLayout = ({
  className = 'layout',
  rowHeight = 30,
  cols = { lg: 12, md: 10, sm: 6, xs: 4, xxs: 2 },
  // isResizable: false,
  // initialLayout = async () => await generateLayout(),
  onLayoutChange = function () {},
}: layoutProps) => {
  const [currentBreakpoint, setCurrentBreakpoint] = useState('lg');
  const [compactType, setCompactType] = useState('vertical');
  const [mounted, setMounted] = useState(false);
  const [pendingCountData, setPendingCountData] = useState({});
  const [acknoledgementDataCount, setAcknoledgementDataCount] = useState(0);

  const [layouts, setLayouts] = useState({ lg: [] });
  const [dashboardModel, setDashboardModel] = useState([]);
  const [loading, setLoading] = useState(true);
  const access = useAccess();
  const { hasAnyPermission, hasPermitted } = access;

  useEffect(() => {
    setMounted(true);
  }, []);

  useEffect(() => {
    if (!dashboardModel) {
      getModel(Models.Dashboard).then((model) => {
        if (model && model.data) {
          setDashboardModel(model.data.modelDataDefinition.fields.layout.attributes);
        }
      });
    }
  });

  useEffect(() => {
    if (hasPermitted('todo-request-access')) {
      fetchRequestCount();
    }

    if (hasPermitted('document-manager-employee-access')) {
      fetchDocumentAcknowledgeCount();
    }
    (async function () {
      await setLayouts({ lg: await generateLayout2() });
    })();
  }, []);

  const fetchRequestCount = async () => {
    try {
      const data = await getPendingRequestCount();
      await setPendingCountData(data.data);
    } catch (error) {
      console.log(error);
    }
  };

  const fetchDocumentAcknowledgeCount = async () => {
    try {
      const data = await getDocumentAcknowledgeCount();
      await setAcknoledgementDataCount(data.data);
    } catch (error) {
      console.log(error);
    }
  };

  const generateLayout2 = async () => {
    let setReturned = [];
    await getDashboard().then(async (model) => {
      let responce = model.data;
      responce?.forEach((item: any, i: number) => {
        // if (hasAnyPermission(item.data.hasAccess)) {
        setReturned.push({
          x: item.x,
          y: item.y,
          w: item.w,
          h: item.h,
          i: item.i,
          static: item.static,
          data: item.data,
        });
        // }
      });
    });
    return setReturned;
  };

  const generateDOM = () => {
    return _.map(layouts.lg, (l, i) => {
      return (
        <div
          key={l.i}
          className={l.static ? 'static' : ''}
          style={{
            border: 0,
            borderRadius: '32px',
          }}
        >
          <CardLayout
            title={l.data.title}
            // loading={loading}
            // cardWidth={400}
            pendingCountData={pendingCountData}
            acknoledgementDataCount={acknoledgementDataCount}
            cardHeight={308}
            viewMoreText={l.data.viewMoreText ?? l.data.viewMoreText}
            viewMoreLink={l.data.viewMoreLink ?? l.data.viewMoreLink}
            fieldData={l.data.fieldData}
            data={l.data}
            fields={l.data.fields}
            //  hidden={hasAnyPermission(l.data.hasAccess)}
          />
        </div>
      );
    });
  };

  const onBreakpointChange = (breakpoint: any) => {
    setCurrentBreakpoint(breakpoint);
  };

  const onLayoutChangeFunc = (layout, layouts) => {
    const currentLayout = layout;

    dashboardModel.forEach((element) => {
      if (_.find(layout, (o) => o.i === element.i) === undefined) {
        const omited = _.omit(element, 'data');
        currentLayout.push(omited);
      }
    });
    onLayoutChange(currentLayout);
  };

  return (
    <div>
      <ResponsiveReactGridLayout
        // {...props}
        {...className}
        {...rowHeight}
        {...cols}
        isResizable={false}
        // resize = {false}
        layouts={layouts}
        onBreakpointChange={onBreakpointChange}
        onLayoutChange={onLayoutChangeFunc}
        // WidthProvider option
        measureBeforeMount={true}
        // I like to have it animate on mount. If you don't, delete `useCSSTransforms` (it's default `true`)
        // and set `measureBeforeMount={true}`.
        useCSSTransforms={mounted}
        compactType={compactType}
        preventCollision={!compactType}
      >
        {generateDOM()}
      </ResponsiveReactGridLayout>
    </div>
  );
};

Dash_ShowcaseLayout.propTypes = {
  onLayoutChange: PropTypes.func.isRequired,
};

export default Dash_ShowcaseLayout;
